#Requires -Version 5.1
<#
.SYNOPSIS
  M6 ad-platform crawler-exposure audit (review ref M6).

.DESCRIPTION
  AC1: Fetch landers + portal as AdsBot-Google and facebookexternalhit; grep banned terms.
  AC2: Optional direct origin IP probe (bypass Cloudflare edge).
  AC3: Plain no-cookie fetch of a portal URL (no JS).
  AC4: Scan portal HTML for Clixtell / GTM / Meta pixel markers.
  Writes evidence under scripts/compliance/evidence/<timestamp>/

  Official browser tools (manual - save screenshots/HAR into evidence folder):
  - Facebook: https://developers.facebook.com/tools/debug/  (facebookexternalhit)
  - Google:   Search Console → URL inspection → Live test (Googlebot)
  - Google:   https://search.google.com/test/rich-results (Googlebot fetch)

.PARAMETER ConfigPath
  JSON config (copy config.example.json → config.json).

.PARAMETER SkipOrigin
  Skip origin IP bypass test.

.EXAMPLE
  cd scripts\compliance
  Copy-Item config.example.json config.json
  # Edit config.json with prod domain + origin IP
  .\run-m6-crawler-audit.ps1
#>
param(
    [string] $ConfigPath = (Join-Path $PSScriptRoot 'config.json'),
    [switch] $SkipOrigin
)

$ErrorActionPreference = 'Stop'

function Write-Section([string] $Title) {
    Write-Host ""
    Write-Host "=== $Title ===" -ForegroundColor Cyan
}

function Get-BannedTerms([string] $TermsFile) {
    if (-not (Test-Path $TermsFile)) {
        throw "Banned terms file not found: $TermsFile"
    }
    return Get-Content $TermsFile |
        Where-Object { $_ -and $_ -notmatch '^\s*#' } |
        ForEach-Object { $_.Trim() } |
        Where-Object { $_ -ne '' }
}

function Invoke-CurlFetch {
    param(
        [string] $Url,
        [string] $UserAgent,
        [string] $OutFile,
        [string] $LogFile
    )
    $meta = curl.exe -sS -L -A $UserAgent -w "`nHTTP_CODE:%{http_code}`nEFFECTIVE:%{url_effective}`n" -o $OutFile $Url 2>&1
    $line = "[$(Get-Date -Format o)] UA=$UserAgent URL=$Url"
    $line | Add-Content $LogFile
    $meta | Add-Content $LogFile
    Add-Content $LogFile '---'
    return $meta
}

function Test-BannedTermsInFile {
    param(
        [string] $FilePath,
        [string[]] $Terms
    )
    $matches = @()
    $content = Get-Content $FilePath -Raw -ErrorAction SilentlyContinue
    if (-not $content) {
        return $matches
    }
    foreach ($term in $Terms) {
        if ($content -match [regex]::Escape($term)) {
            $matches += $term
        }
    }
    return $matches | Select-Object -Unique
}

function Test-PortalBlockedContent {
    param([string] $FilePath, [string] $EffectiveUrl)
    $content = Get-Content $FilePath -Raw -ErrorAction SilentlyContinue
    if (-not $content) {
        return @{ blocked = $false; reason = 'empty response' }
    }
    $onVerify = $EffectiveUrl -match 'portal/verify'
    $hasTurnstileMsg = $content -match 'Verifying you are human|challenges\.cloudflare\.com|turnstile'
    $hasPortalApp = $content -match 'sett-title|portal-content|av-baccarat|hq_game_url|world-tab|ihq-concierge-fab'
    if ($onVerify -or $hasTurnstileMsg) {
        if (-not $hasPortalApp) {
            return @{ blocked = $true; reason = 'verify/turnstile only' }
        }
        return @{ blocked = $false; reason = 'verify page but portal app markers found' }
    }
    if ($hasPortalApp) {
        return @{ blocked = $false; reason = 'portal app content exposed' }
    }
    return @{ blocked = $true; reason = 'no portal app markers (likely blocked or minimal)' }
}

function Test-TrackingMarkers {
    param([string] $FilePath)
    $content = Get-Content $FilePath -Raw -ErrorAction SilentlyContinue
    if (-not $content) {
        return @()
    }
    $patterns = @(
        'clixtell',
        'scripts\.clixtell\.com',
        'googletagmanager\.com',
        'gtm\.js',
        'GTM-',
        'fbevents',
        'connect\.facebook\.net',
        'fbq\(',
        'facebook-domain-verification'
    )
    $found = @()
    foreach ($p in $patterns) {
        if ($content -match $p) {
            $found += $p
        }
    }
    return $found | Select-Object -Unique
}

if (-not (Test-Path $ConfigPath)) {
    Write-Host "Copy config.example.json to config.json and set baseUrl + paths." -ForegroundColor Yellow
    throw "Missing config: $ConfigPath"
}

$config = Get-Content $ConfigPath -Raw | ConvertFrom-Json
$baseUrl = $config.baseUrl.TrimEnd('/')
$termsFile = Join-Path $PSScriptRoot 'banned-terms.txt'
$terms = Get-BannedTerms $termsFile

$stamp = Get-Date -Format 'yyyy-MM-dd_HHmmss'
$evidenceRoot = Join-Path $PSScriptRoot "evidence\$stamp"
$dirs = @('crawler-fetch', 'grep', 'no-js', 'origin-bypass', 'pixel-scan', 'summary')
foreach ($d in $dirs) {
    New-Item -ItemType Directory -Force -Path (Join-Path $evidenceRoot $d) | Out-Null
}

$summaryPath = Join-Path $evidenceRoot 'summary\report.txt'
"M6 crawler audit - $stamp" | Set-Content $summaryPath
"baseUrl: $baseUrl" | Add-Content $summaryPath

$bots = @{
    'AdsBot-Google'      = 'AdsBot-Google (+http://www.google.com/adsbot.html)'
    'facebookexternalhit' = 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)'
}

$crawlerLog = Join-Path $evidenceRoot 'crawler-fetch\fetch-log.txt'
$grepReport = Join-Path $evidenceRoot 'grep\banned-term-matches.txt'
$landerFail = $false
$portalFail = $false

Write-Section 'AC1 - Crawler fetch (AdsBot + Facebook) + banned-term grep'

foreach ($botKey in $bots.Keys) {
    $ua = $bots[$botKey]
    foreach ($path in $config.landers) {
        $url = $baseUrl + $path
        $safe = ($botKey + '_lander_' + ($path -replace '[^\w]+', '_')).Trim('_')
        $out = Join-Path $evidenceRoot "crawler-fetch\$safe.html"
        $meta = Invoke-CurlFetch -Url $url -UserAgent $ua -OutFile $out -LogFile $crawlerLog
        $hits = Test-BannedTermsInFile -FilePath $out -Terms $terms
        if ($hits.Count -gt 0) {
            $landerFail = $true
            "FAIL lander $path ($botKey): $($hits -join ', ')" | Add-Content $grepReport
        } else {
            "PASS lander $path ($botKey): zero banned terms" | Add-Content $grepReport
        }
    }
    foreach ($path in $config.portal) {
        $url = $baseUrl + $path
        $safe = ($botKey + '_portal_' + ($path -replace '[^\w]+', '_')).Trim('_')
        $out = Join-Path $evidenceRoot "crawler-fetch\$safe.html"
        $meta = Invoke-CurlFetch -Url $url -UserAgent $ua -OutFile $out -LogFile $crawlerLog
        $effective = ($meta | Select-String 'EFFECTIVE:(.+)' | ForEach-Object { $_.Matches.Groups[1].Value } | Select-Object -Last 1)
        $block = Test-PortalBlockedContent -FilePath $out -EffectiveUrl $effective
        if (-not $block.blocked) {
            $portalFail = $true
            "FAIL portal $path ($botKey): $($block.reason)" | Add-Content $grepReport
        } else {
            "PASS portal $path ($botKey): $($block.reason)" | Add-Content $grepReport
        }
        $pixels = Test-TrackingMarkers -FilePath $out
        if ($pixels.Count -gt 0) {
            "WARN portal $path ($botKey) tracking markers: $($pixels -join ', ')" | Add-Content (Join-Path $evidenceRoot 'pixel-scan\portal-crawler.txt')
        }
    }
}

Write-Section 'AC3 - Plain fetch (no cookies, curl - simulates no-JS proxy client)'
$plainPath = $config.plainFetchPortalPath
$plainUrl = $baseUrl + $plainPath
$plainOut = Join-Path $evidenceRoot 'no-js\portal-plain.html'
$plainMeta = Invoke-CurlFetch -Url $plainUrl -UserAgent 'curl/8.0 (M6-compliance-audit)' -OutFile $plainOut -LogFile (Join-Path $evidenceRoot 'no-js\fetch-log.txt')
$plainEffective = ($plainMeta | Select-String 'EFFECTIVE:(.+)' | ForEach-Object { $_.Matches.Groups[1].Value } | Select-Object -Last 1)
$plainBlock = Test-PortalBlockedContent -FilePath $plainOut -EffectiveUrl $plainEffective
$plainPass = $plainBlock.blocked
"Plain portal fetch: $(if ($plainPass) { 'PASS' } else { 'FAIL' }) - $($plainBlock.reason) - $plainEffective" | Add-Content $summaryPath

Write-Section 'AC4 - Tracking markers on portal crawler responses'
$pixelReport = Join-Path $evidenceRoot 'pixel-scan\summary.txt'
Get-ChildItem (Join-Path $evidenceRoot 'crawler-fetch\*portal*.html') -ErrorAction SilentlyContinue | ForEach-Object {
    $px = Test-TrackingMarkers -FilePath $_.FullName
    if ($px.Count -gt 0) {
        "$($_.Name): $($px -join ', ')" | Add-Content $pixelReport
    }
}
if (-not (Test-Path $pixelReport)) {
    'PASS: no Clixtell/GTM/Meta markers in portal crawler HTML' | Set-Content $pixelReport
}

Write-Section 'AC2 - Origin IP bypass (optional)'
$originPass = $null
if ($SkipOrigin -or -not $config.originIp) {
    'SKIPPED: set originIp in config.json or omit -SkipOrigin' | Add-Content $summaryPath
} else {
    $hostHeader = $config.hostHeader
    $ip = $config.originIp
    $httpLog = Join-Path $evidenceRoot 'origin-bypass\http.log'
    $httpsLog = Join-Path $evidenceRoot 'origin-bypass\https.log'
    curl.exe -v --max-time 15 --resolve "${hostHeader}:80:${ip}" "http://${hostHeader}/" -o (Join-Path $evidenceRoot 'origin-bypass\http.html') 2>&1 | Set-Content $httpLog
    curl.exe -v -k --max-time 15 --resolve "${hostHeader}:443:${ip}" "https://${hostHeader}/" -o (Join-Path $evidenceRoot 'origin-bypass\https.html') 2>&1 | Set-Content $httpsLog
  $httpBody = Get-Content (Join-Path $evidenceRoot 'origin-bypass\http.html') -Raw -ErrorAction SilentlyContinue
  $originPass = ($httpLog -match 'Connection refused|timed out|Could not connect|403 Forbidden') -or ($httpBody.Length -lt 200)
    "Origin bypass: $(if ($originPass) { 'PASS (blocked or empty)' } else { 'REVIEW - full response saved' })" | Add-Content $summaryPath
}

Write-Section 'Summary'
$ac1 = (-not $landerFail) -and (-not $portalFail)
"AC1 crawler+grep: $(if ($ac1) { 'PASS' } else { 'FAIL' })" | Add-Content $summaryPath
"AC3 plain portal:  $(if ($plainPass) { 'PASS' } else { 'FAIL' })" | Add-Content $summaryPath
"Evidence: $evidenceRoot" | Add-Content $summaryPath

Write-Host ""
Write-Host "Done. Evidence: $evidenceRoot" -ForegroundColor Green
Write-Host ""
Write-Host "Also run these official tools and save screenshots into the evidence folder:" -ForegroundColor Yellow
Write-Host "  Facebook Sharing Debugger: https://developers.facebook.com/tools/debug/"
Write-Host "  Google Search Console:     URL Inspection → Test live URL (per lander URL)"
Write-Host "  Google Rich Results Test:  https://search.google.com/test/rich-results"
Write-Host "  Meta Pixel Helper (Chrome): scan portal pages while logged out"
Write-Host ""

Get-Content $summaryPath
