/**
 * AI Coach — page-home-aicoach.php screen sequence (Sami avatar + panels).
 * Config expected on window.AICOACH_SAMI (see inc/anam-proxy.php enqueue).
 */
import { createClient, AnamEvent } from 'https://cdn.jsdelivr.net/npm/@anam-ai/js-sdk@4/+esm';

/*
 * PO-3062 avatar connection — as of 2026-09-15, the avatar/video is opened via
 * a real Gary Coach API session (inc/gary-proxy.php's /coach/session), not a
 * directly-minted Anam token anymore. Gary's response includes an Anam
 * session_token (say.video.session_token) that the same Anam client SDK
 * connects with — Gary is effectively brokering the Anam session for us now.
 *
 * KNOWN, FLAGGED GAP: Gary's API has no "say this exact text" capability — it
 * generates its own contextual response, it doesn't recite a script we hand
 * it. That means only the FIRST thing the avatar says (its own generated
 * opening line, shown live from Gary's session-open response) is real,
 * Gary-spoken content. Every screen after that (We Believe, equity examples,
 * competition types, etc.) still uses this file's existing fixed SCREENS
 * copy, but displayed as a static caption — same pacing as the
 * no-connection fallback path — rather than spoken/lip-synced, since there's
 * no way to make Gary recite it. This is a known content gap, not a bug:
 * confirmed with Filip Milinkovic (2026-09-15) that connecting the avatar
 * should proceed anyway while Sami's Gary-side prompt/config is separately
 * requested to cover IHQ's registration content ("this isn't a technical
 * blocker, continue connecting the API"). Revisit this file once that
 * config lands — at that point some/all of SCREENS' copy may become real
 * Gary `event`/`message` calls instead of static display.
 *
 * FR-01 + FR-02 + FR-03 — on arrival, the coach's own real greeting plays
 * live, then the rest of the fixed sequence (intro, two "We Believe"
 * screens, then the time-selection pitch — this last one spoken over the
 * existing tier-selection UI rather than a separate screen) is shown as
 * static captions per the gap above. Each screen advances to the next after
 * a reading dwell, or immediately on a visitor tap/click (FR-02's
 * "narration timing or visitor tap/click"); confirming a time tier (FR-03)
 * is the same kind of early-advance, plus it marks the sequence done and
 * starts elapsed-time tracking. Unlike page-portal-poc.php this is NOT
 * tap-to-start — the AC requires the video to begin on load.
 */
const FADE_MS = 400;
const FALLBACK_READ_MS = 9000; // per-screen dwell for the static-text fallback (no speech to sync against)
const AVATAR_VIDEO_ID = 'aicoach-avatar-video';

// PO-3346 — Ask Sami's openPanel() previously only paused the <video>
// element; the sequence's own dwell/safety timers (waitForReadOrSkip(),
// playPrerenderedClip()'s 60s cap) had no idea the panel was open and kept
// counting down, so the sequence could advance to a later screen — or start
// a new clip — while the panel was still open on top of it. Wrapping those
// timers in something pause-aware, instead of a bare setTimeout(), is what
// lets openPanel()/closePanel() below actually stop and resume them instead
// of just hiding the video that was playing.
//
// Deliberately does NOT cover waitForSpeechOrSkip() (the live intro's own
// wait) — pausing our local video rendering doesn't pause the real,
// server-side Gary/Anam conversation, so there's no "resume from where we
// left off" for that one the way there is for a fixed dwell or a local
// clip's safety cap. Ask Sami is technically clickable that early (a narrow
// window), and that gap is unaddressed — tracked, not silently assumed away.
const pausableSequenceTimers = new Set();

function createPausableTimeout( callback, ms ) {
    let remaining = ms;
    let timerId = null;
    let armedAt = null;
    const handle = {
        pause() {
            if ( null === timerId ) {
                return;
            }
            window.clearTimeout( timerId );
            timerId = null;
            remaining = Math.max( 0, remaining - ( Date.now() - armedAt ) );
        },
        resume() {
            if ( null !== timerId ) {
                return; // already running — pause()/resume() calls aren't expected to nest
            }
            armedAt = Date.now();
            timerId = window.setTimeout( function () {
                timerId = null;
                pausableSequenceTimers.delete( handle );
                callback();
            }, remaining );
        },
        cancel() {
            if ( null !== timerId ) {
                window.clearTimeout( timerId );
                timerId = null;
            }
            pausableSequenceTimers.delete( handle );
        },
    };
    pausableSequenceTimers.add( handle );
    handle.resume();
    return handle;
}

function pauseSequenceTimers() {
    pausableSequenceTimers.forEach( function ( timer ) {
        timer.pause();
    } );
}

function resumeSequenceTimers() {
    pausableSequenceTimers.forEach( function ( timer ) {
        timer.resume();
    } );
}

// FR-17 — trigger the time-remaining check once this proportion of the
// selected tier's total duration has elapsed. The ticket explicitly says the
// real threshold "requires confirmation" — 0.8 (80%) is a placeholder pending
// that, not an approved value. Adjust here once it's confirmed.
const TIME_REMAINING_THRESHOLD_RATIO = 0.8;
const TIER_DURATION_MS = { '2': 2 * 60 * 1000, '5': 5 * 60 * 1000, '10': 10 * 60 * 1000 };

// FR-13 — manual language selector (Scenario 24/25). Native labels are the ones
// given verbatim in the ticket itself ("Proposed native-language labels, subject to
// localisation review") — not invented here. Locale codes match Gary's
// approved_languages exactly (confirmed via GET /coach/v1/health).
const SUPPORTED_LOCALES = [
    { code: 'en', nativeLabel: 'English' },
    { code: 'zh', nativeLabel: '普通话' },
    { code: 'yue', nativeLabel: '廣東話' },
    { code: 'ja', nativeLabel: '日本語' },
    { code: 'ko', nativeLabel: '한국어' },
    { code: 'th', nativeLabel: 'ไทย' },
    { code: 'vi', nativeLabel: 'Tiếng Việt' },
];

// English is the real, approved copy — source of truth for translation. The other
// six languages are deliberately left EMPTY, not filled with invented/machine
// translations: no approved copy exists for them yet. t() below falls back to
// English for any missing key. Fill these in only once real, PO-approved
// translations are supplied — same "never invent unapproved copy" rule this whole
// epic has followed for every other TBD piece of content.
const I18N_EN = {
    belief: 'We believe conversations should be easy.',
    weBelieve: 'We Believe',
    tierGroupLabel: 'Conversation length',
    'tier-2-duration': '2 minutes',
    'tier-2-item-0': 'Introduction to Competition',
    'tier-2-item-1': 'Choose Communication Method',
    'tier-5-duration': '5 minutes',
    'tier-5-item-0': 'Introduction to Competition',
    'tier-5-item-1': 'Choose Communication Method',
    'tier-5-item-2': 'Full Competition Details',
    'tier-10-duration': '10 minutes',
    'tier-10-item-0': 'Introduction to Competition',
    'tier-10-item-1': 'Choose Communication Method',
    'tier-10-item-2': 'Full Competition Details',
    'tier-10-item-3': 'Setup your First Competition to Earn Equity',
    'tier-10-item-4': 'Help with Creating Posts for Your Followers',
    magicAdidas: 'Adidas $100k cash',
    magicNike: "Nike's 11 cent stock",
    magicNikeWorth: 'Now worth 5.4 billion',
    alixPoppi: 'Said yes to an ownership-based partnership with Poppi',
    turnedDownCash: 'Turned down cash',
    btsOwnership: 'Said yes to shared ownership, valued at 103.6 million',
    worldCompetitionName: 'World Competition',
    youFollowers: 'You + Followers',
    versus: 'versus',
    allInfluencersFollowers: 'All Influencers + Followers',
    communityCompetitionName: 'Community Competition',
    communityCompetitionFormat: 'Weekly competition for your followers only',
    privateChallengeName: 'Private Challenge',
    friendFollowers: 'An Influencer Friend + Followers',
    identityTitle: "Let's Start The Conversation",
    identitySubtitle: 'Let us know who you are',
    firstName: 'First Name',
    lastName: 'Last Name',
    username: 'Username',
    continue: 'Continue',
    channelsHint: 'Please select at least one communication method.',
    letsContinue: "Let's Continue",
    timeCheckText: "Looks like your selected time is almost up. Do you have a few more minutes to finish?",
    yes: 'Yes',
    no: 'No',
    // Mirrors $aicoach_channels in page-home-aicoach.php — kept in sync by hand,
    // there's no single shared source between PHP and this file for it.
    'channel-email-label': 'Email',
    'channel-email-inputLabel': 'Email address',
    'channel-email-placeholder': 'you@example.com',
    'channel-kakaotalk-label': 'KakaoTalk',
    'channel-kakaotalk-inputLabel': 'KakaoTalk ID or phone number',
    'channel-kakaotalk-placeholder': '',
    'channel-line-label': 'Line',
    'channel-line-inputLabel': 'Line ID',
    'channel-line-placeholder': 'U0123456789abcdef0123456789abcdef',
    'channel-sms-label': 'SMS',
    'channel-sms-inputLabel': 'Phone number',
    'channel-sms-placeholder': '+66812345678',
    'channel-telegram-label': 'Telegram',
    'channel-telegram-inputLabel': 'Telegram username or chat ID',
    'channel-telegram-placeholder': '@username',
    'channel-wechat-label': 'WeChat',
    'channel-wechat-inputLabel': 'WeChat ID',
    'channel-wechat-placeholder': '',
    'channel-whatsapp-label': 'WhatsApp',
    'channel-whatsapp-inputLabel': 'Phone number',
    'channel-whatsapp-placeholder': '+66812345678',
    'channel-zalo-label': 'Zalo',
    'channel-zalo-inputLabel': 'Phone number or Zalo ID',
    'channel-zalo-placeholder': '',
};

// PO-3104 (FR-13) — UI text translations, sourced the same way as
// inc/aicoach-segment-translations.php's avatar scripts: programmatically
// matched against the real translated sheet (not hand-transcribed), diffing
// each candidate row's English column against I18N_EN's own value before
// taking its other six columns, so a mismatch surfaces as a missing key
// instead of silently shipping wrong text. t() below already falls back to
// English for any key a locale's table doesn't have, so every gap here is a
// deliberate omission, not a bug:
//   - Not in the sheet at all yet: tierGroupLabel, channelsHint,
//     timeCheckText, yes, no, and all 8 channel-*-inputLabel /
//     5 channel-*-placeholder keys (the sheet only has the channel NAMES,
//     e.g. "Email", not the input-field copy, e.g. "Email address").
//   - In the sheet but structured differently than this file's keys, so not
//     safely auto-splittable per language without guessing word order:
//     magicNike/magicNikeWorth (one combined sheet cell, two keys here),
//     worldCompetitionName/communityCompetitionName/privateChallengeName
//     (sheet splits a shared "COMPETITION" badge from the title word — this
//     file has them pre-joined, e.g. "World Competition"), and
//     youFollowers/versus/allInfluencersFollowers/friendFollowers (all
//     fused into one sentence in the sheet, e.g. "Weekly You + Followers
//     versus All Influencers + Followers").
//   - Present but excluded after verification found the sheet itself wrong:
//     belief/weBelieve's Japanese cells translate to unrelated stock phrases
//     ("feel free to contact us", "since ancient times") instead of the
//     English column next to them — flagged back to whoever owns the sheet,
//     not guessed around. Korean's FIRST NAME/LAST NAME row has 성 (surname)
//     and 이름 (given name) swapped relative to every other language's
//     column order — possibly intentional for Korean name-order convention,
//     but this file's two fields are fixed input order regardless of
//     locale, so shipping it as-is would mislabel which box is which;
//     left both keys on the English fallback until that's confirmed.
const I18N_TRANSLATIONS = {
    en: I18N_EN,
    zh: {
        belief: '我们相信，沟通应该要很轻松。',
        weBelieve: '我们的信念',
        'tier-2-duration': '2 分钟',
        'tier-2-item-0': '竞赛简介',
        'tier-2-item-1': '选择沟通方式',
        'tier-5-duration': '5 分钟',
        'tier-5-item-0': '竞赛简介',
        'tier-5-item-1': '选择沟通方式',
        'tier-5-item-2': '完整竞赛详情',
        'tier-10-duration': '10 分钟',
        'tier-10-item-0': '竞赛简介',
        'tier-10-item-1': '选择沟通方式',
        'tier-10-item-2': '完整竞赛详情',
        'tier-10-item-3': '设置你的首场竞赛以赚取股权',
        'tier-10-item-4': '协助为你的粉丝创作帖子',
        magicAdidas: '阿迪达斯10 万美元现金',
        alixPoppi: '选择接受以股权为基础的合作伙伴关系，加入了 Poppi',
        turnedDownCash: '拒绝了纯现金报酬',
        btsOwnership: '选择接受价值 1.036 亿美元的共享股权',
        communityCompetitionFormat: '每周专属于你粉丝的竞赛',
        identityTitle: '开始我们的对话吧',
        identitySubtitle: '请让我们知道你是谁。',
        firstName: '名字',
        lastName: '姓氏',
        username: '用户名',
        continue: '继续',
        letsContinue: '我们继续吧。',
        'channel-email-label': '电子邮件',
        'channel-kakaotalk-label': 'KakaoTalk',
        'channel-line-label': 'Line',
        'channel-sms-label': 'SMS 短信',
        'channel-telegram-label': 'Telegram',
        'channel-wechat-label': '微信',
        'channel-whatsapp-label': 'WhatsApp',
        'channel-zalo-label': 'Zalo',
    },
    yue: {
        belief: '我們相信，溝通應該要很輕鬆。',
        weBelieve: '我們的信念',
        'tier-2-duration': '2 分鐘',
        'tier-2-item-0': '競賽簡介',
        'tier-2-item-1': '選擇溝通方式',
        'tier-5-duration': '5 分鐘',
        'tier-5-item-0': '競賽簡介',
        'tier-5-item-1': '選擇溝通方式',
        'tier-5-item-2': '完整競賽詳情',
        'tier-10-duration': '10 分鐘',
        'tier-10-item-0': '競賽簡介',
        'tier-10-item-1': '選擇溝通方式',
        'tier-10-item-2': '完整競賽詳情',
        'tier-10-item-3': '設定你的首場競賽以賺取股權',
        'tier-10-item-4': '協助為你的粉絲創作貼文',
        magicAdidas: '愛迪達10 萬美元現金',
        alixPoppi: '選擇接受以股權為基礎的合作夥伴關係，加入了 Poppi',
        turnedDownCash: '拒絕了純現金報酬',
        btsOwnership: '選擇接受價值 1.036 億美元的共享股權',
        communityCompetitionFormat: '每週專屬於你粉絲嘅競賽',
        identityTitle: '開始我哋嘅對話吧',
        identitySubtitle: '請話畀我哋知你係邊位。',
        firstName: '名字',
        lastName: '姓氏',
        username: '用戶名稱',
        continue: '繼續',
        letsContinue: '我哋繼續吧。',
        'channel-email-label': '電郵',
        'channel-kakaotalk-label': 'KakaoTalk',
        'channel-line-label': 'Line',
        'channel-sms-label': 'SMS',
        'channel-telegram-label': 'Telegram',
        'channel-wechat-label': '微信',
        'channel-whatsapp-label': 'WhatsApp',
        'channel-zalo-label': 'Zalo',
    },
    ja: {
        'tier-2-duration': '所要時間：2分',
        'tier-2-item-0': 'コンペティションについて',
        'tier-2-item-1': 'ご連絡方法を選択',
        'tier-5-duration': '所要時間：5分',
        'tier-5-item-0': 'コンペティションについて',
        'tier-5-item-1': 'ご連絡方法を選択',
        'tier-5-item-2': 'コンペティションの詳細',
        'tier-10-duration': '所要時間：10分',
        'tier-10-item-0': 'コンペティションについて',
        'tier-10-item-1': 'ご連絡方法を選択',
        'tier-10-item-2': 'コンペティションの詳細',
        'tier-10-item-3': '持ち株獲得に向けて、最初のコンペティションを設定',
        'tier-10-item-4': 'フォロワー向け投稿の作成をサポート',
        magicAdidas: 'アディダスは現金10万ドル',
        alixPoppi: 'POPPIとの持ち株型パートナーシップを選択',
        turnedDownCash: '現金報酬を断る',
        btsOwnership: '共同で持ち株を保有する機会を選択、その価値は1億360万ドルに',
        communityCompetitionFormat: '毎週、あなたのフォロワーだけが参加できるコンペティション',
        identityTitle: 'それではやり取りをスタートしましょう。',
        identitySubtitle: 'あなたについて教えてください。',
        firstName: '名',
        lastName: '姓',
        username: 'ユーザー名',
        continue: '続ける',
        letsContinue: '再開しましょう。',
        'channel-email-label': 'Email',
        'channel-kakaotalk-label': 'KakaoTalk',
        'channel-line-label': 'Line',
        'channel-sms-label': 'SMS',
        'channel-telegram-label': 'Telegram',
        'channel-wechat-label': 'WeChat',
        'channel-whatsapp-label': 'WhatsApp',
        'channel-zalo-label': 'Zalo',
    },
    th: {
        belief: 'เราเชื่อว่าการพูดคุยกันควรเป็นเรื่องง่าย',
        weBelieve: 'ความเชื่อของเรา',
        'tier-2-duration': '2 นาที',
        'tier-2-item-0': 'ทำความรู้จักการแข่งขัน',
        'tier-2-item-1': 'เลือกช่องทางพูดคุย',
        'tier-5-duration': '5 นาที',
        'tier-5-item-0': 'ทำความรู้จักการแข่งขัน',
        'tier-5-item-1': 'เลือกช่องทางพูดคุย',
        'tier-5-item-2': 'รายละเอียดการแข่งขันทั้งหมด',
        'tier-10-duration': '10 นาที',
        'tier-10-item-0': 'ทำความรู้จักการแข่งขัน',
        'tier-10-item-1': 'เลือกช่องทางพูดคุย',
        'tier-10-item-2': 'รายละเอียดการแข่งขันทั้งหมด',
        'tier-10-item-3': 'ตั้งค่าการแข่งขันครั้งแรกของคุณเพื่อรับหุ้นปันผล',
        'tier-10-item-4': 'ช่วยทำโพสต์สำหรับผู้ติดตามของคุณ',
        magicAdidas: 'อาดิดาส: เงินสด 100,000 ดอลลาร์',
        alixPoppi: 'ตกลงร่วมเป็นพาร์ตเนอร์และถือหุ้นใน Poppi',
        turnedDownCash: 'ปฏิเสธข้อเสนอเงินสด',
        btsOwnership: 'ตกลงร่วมถือหุ้น มูลค่า 103.6 ล้านดอลลาร์',
        communityCompetitionFormat: 'การแข่งขันประจำสัปดาห์สำหรับผู้ติดตามของคุณเท่านั้น',
        identityTitle: 'มาเริ่มคุยกันเลย',
        identitySubtitle: 'แนะนำตัวให้เรารู้จักหน่อย',
        firstName: 'ชื่อ',
        lastName: 'นามสกุล',
        username: 'ชื่อผู้ใช้',
        continue: 'ดำเนินการต่อ',
        letsContinue: 'ไปต่อกันเลย',
        'channel-email-label': 'อีเมล',
        'channel-kakaotalk-label': 'KakaoTalk',
        'channel-line-label': 'LINE',
        'channel-sms-label': 'SMS',
        'channel-telegram-label': 'Telegram',
        'channel-wechat-label': 'WeChat',
        'channel-whatsapp-label': 'WhatsApp',
        'channel-zalo-label': 'Zalo',
    },
    vi: {
        belief: 'Chúng tôi tin rằng việc trò chuyện nên thật dễ dàng.',
        weBelieve: 'Chúng tôi tin rằng',
        'tier-2-duration': '2 phút',
        'tier-2-item-0': 'Giới Thiệu Về Cuộc Thi',
        'tier-2-item-1': 'Chọn Phương Thức Liên Lạc',
        'tier-5-duration': '5 phút',
        'tier-5-item-0': 'Giới Thiệu Về Cuộc Thi',
        'tier-5-item-1': 'Chọn Phương Thức Liên Lạc',
        'tier-5-item-2': 'Thông Tin Chi Tiết Về Cuộc Thi',
        'tier-10-duration': '10 phút',
        'tier-10-item-0': 'Giới Thiệu Về Cuộc Thi',
        'tier-10-item-1': 'Chọn Phương Thức Liên Lạc',
        'tier-10-item-2': 'Thông Tin Chi Tiết Về Cuộc Thi',
        'tier-10-item-3': 'Thiết Lập Cuộc Thi Đầu Tiên Để Nhận Cổ Phần',
        'tier-10-item-4': 'Hỗ Trợ Tạo Bài Đăng Cho Người Theo Dõi Của Bạn',
        magicAdidas: 'ADIDAS 100 NGHÌN USD TIỀN MẶT',
        alixPoppi: 'ĐỒNG Ý HỢP TÁC VỚI POPPI ĐỂ SỞ HỮU CỔ PHẦN',
        turnedDownCash: 'TỪ CHỐI NHẬN TIỀN MẶT',
        btsOwnership: 'ĐỒNG Ý VIỆC ĐỒNG SỞ HỮU CỔ PHẦN TRỊ GIÁ 103,6 TRIỆU',
        communityCompetitionFormat: 'CUỘC THI Hằng Tuần Chỉ Dành Cho NGƯỜI THEO DÕI Của Bạn',
        identityTitle: 'Bắt Đầu Trò Chuyện',
        identitySubtitle: 'Hãy cho chúng tôi biết bạn là ai.',
        firstName: 'TÊN',
        lastName: 'HỌ',
        username: 'TÊN NGƯỜI DÙNG',
        continue: 'TIẾP TỤC',
        letsContinue: 'Hãy Tiếp Tục.',
        'channel-email-label': 'Email',
        'channel-kakaotalk-label': 'KakaoTalk',
        'channel-line-label': 'Line',
        'channel-sms-label': 'SMS',
        'channel-telegram-label': 'Telegram',
        'channel-wechat-label': 'WeChat',
        'channel-whatsapp-label': 'WhatsApp',
        'channel-zalo-label': 'Zalo',
    },
    ko: {
        belief: '우리는 대화가 쉬워야 한다고 믿습니다.',
        weBelieve: '우리의 신념',
        'tier-2-duration': '2분',
        'tier-2-item-0': '대회 소개',
        'tier-2-item-1': '소통방식 선택',
        'tier-5-duration': '5분',
        'tier-5-item-0': '대회 소개',
        'tier-5-item-1': '소통방식 선택',
        'tier-5-item-2': '대회 세부사항 전체',
        'tier-10-duration': '10분',
        'tier-10-item-0': '대회 소개',
        'tier-10-item-1': '소통방식 선택',
        'tier-10-item-2': '대회 세부사항 전체',
        'tier-10-item-3': '지분 획득을 위한 첫 번째 대회 설정하기',
        'tier-10-item-4': '팔로워를 위한 게시물 작성 지원',
        magicAdidas: '아디다스 현금 10만 달러',
        alixPoppi: '포피(POPPI)와의 지분 기반 파트너십을 수락함',
        turnedDownCash: '현금을 거절함.',
        btsOwnership: '1억 360만 달러 가치의 공동 지분 소유를 수락함.',
        communityCompetitionFormat: '오직 당신의 팔로워만을 위한 주간 대회',
        identityTitle: '대화를 시작해 봅시다.',
        identitySubtitle: '누구신지 알려주세요',
        username: '사용자 이름(아이디)',
        continue: '계속',
        letsContinue: '계속해 봅시다.',
        'channel-email-label': '이메일',
        'channel-kakaotalk-label': '카카오톡',
        'channel-line-label': '라인',
        'channel-sms-label': 'SMS',
        'channel-telegram-label': '텔레그램',
        'channel-wechat-label': '위챗',
        'channel-whatsapp-label': '왓츠앱',
        'channel-zalo-label': '잘로',
    },

};

function t( locale, key ) {
    const table = I18N_TRANSLATIONS[ locale ] || {};
    return table[ key ] || I18N_EN[ key ] || '';
}

// Walks every data-i18n / data-i18n-attr element and applies the given locale's
// copy (falling back to English per t() above). Static text/attributes only —
// spoken captions come from Sami's own responses, not this table.
function applyLocale( locale ) {
    document.querySelectorAll( '[data-i18n]' ).forEach( function ( el ) {
        el.textContent = t( locale, el.getAttribute( 'data-i18n' ) );
    } );
    document.querySelectorAll( '[data-i18n-attr]' ).forEach( function ( el ) {
        el.getAttribute( 'data-i18n-attr' ).split( ',' ).forEach( function ( pair ) {
            const [ attr, key ] = pair.split( ':' );
            el.setAttribute( attr, t( locale, key ) );
        } );
    } );
}

// FR-12 — language auto-detected from browser locale, with English fallback
// (Scenario 22/23). The ticket's priority order is: saved Luna preference →
// browser locale → English. Only the last two are implemented here — Luna
// session persistence (PO-3102) doesn't exist yet, so there's no saved
// preference to read. Revisit this once PO-3102 ships.
function detectInitialLocale() {
    const supportedCodes = SUPPORTED_LOCALES.map( function ( loc ) { return loc.code; } );
    const browserTags = ( navigator.languages && navigator.languages.length )
        ? navigator.languages
        : [ navigator.language || '' ];

    for ( let i = 0; i < browserTags.length; i++ ) {
        const tag = ( browserTags[ i ] || '' ).toLowerCase();
        if ( ! tag ) {
            continue;
        }
        const primary = tag.split( '-' )[ 0 ];
        // Cantonese has no primary subtag browsers actually report — "yue" is
        // valid BCP 47 but essentially never seen in the wild. Hong Kong/Macau
        // region on "zh" is the closest practical signal; any other "zh" region
        // (or bare "zh") is treated as Mandarin, matching SUPPORTED_LOCALES.
        if ( primary === 'yue' ) {
            return 'yue';
        }
        if ( primary === 'zh' && /^zh(?:-[a-z]{4})*-(?:hk|mo)(?:-|$)/.test( tag ) ) {
            return 'yue';
        }
        if ( supportedCodes.indexOf( primary ) !== -1 ) {
            return primary;
        }
    }
    return 'en';
}

const SCREENS = [
    {
        panel: 'intro',
        script: "Hello. I'm Sami. Your Executive Coach. Welcome to InfluencerHQ. My job is to help you understand one simple idea. We believe influencers who help build our company should have the opportunity to earn meaningful equity. Over the next few minutes, I'll show you why we believe that… how successful people have benefited from ownership… and how InfluencerHQ can help you begin your own journey. You don't need to remember everything today. I'll always be here to answer your questions… continue exactly where we leave off… or meet with you whenever you'd like. Let's begin.",
    },
    {
        panel: 'believe-1',
        script: "Every successful company begins with a set of beliefs. Here's one of ours. We believe influence is about more than creating content. It's about creating lasting value. Most influencers are rewarded for what they do today. We believe influencers who help build tomorrow should also have the opportunity to benefit from what they help create. That's why meaningful equity is at the heart of InfluencerHQ. Let me show you what I mean.",
    },
    {
        panel: 'believe-2',
        script: "There's another belief that's just as important. We believe the people who help create value should have the opportunity to share in that value. Not someday… From the very beginning. That's very different from the traditional way most influencers are rewarded. When meaningful ownership is available, it can become worth far more than a one-time payment. This isn't just our opinion. Let me show you a few real examples.",
    },
    {
        // FR-03 — spoken over the existing tier-selection UI (the "home" panel), not a
        // separate screen: the AC has the coach's script play while the three time
        // options are already on screen, not before them.
        panel: 'home',
        script: "Now it's your turn. How much time would you like to spend with me today? Whether you have two minutes… five minutes… or ten minutes… I'll make sure our time together is worthwhile. Simply choose the amount of time that works best for you… and I'll personally guide you every step of the way. And remember… if we don't finish today… we'll simply continue exactly where we leave off. Go ahead… choose the amount of time that's right for you.",
    },
];

// FR-05 — equity examples. Which of these play, and in what order, depends on
// the tier confirmed in FR-03 (see getEquityScreensForTier below); they are
// appended to SCREENS at that point rather than being fixed here upfront.
const EQUITY_SCREENS = {
    magic: {
        panel: 'equity-magic',
        script: "Basketball star Magic Johnson was offered one of the greatest ownership opportunities in history. Instead… he accepted a traditional endorsement. That decision has been estimated to have cost him approximately $5.4 billion in ownership value. No one can predict the future. Not every ownership opportunity succeeds. But when the right ownership opportunity comes along… it can become worth far more than immediate cash. Today… for the first time… influencers are beginning to receive similar ownership opportunities. Let's look at one.",
    },
    alix: {
        panel: 'equity-alix',
        script: "Influencer Alix Earle made a different decision. Instead of accepting only a traditional cash sponsorship… she negotiated an ownership opportunity with Poppi. Less than three years later… PepsiCo acquired Poppi for nearly $2 billion. Her story reminds us that ownership opportunities are no longer limited to athletes, entertainers, or business leaders. Today… influencers also have the opportunity to think beyond immediate cash… and participate in the long-term value they help create. Now… let's look at an international example.",
    },
    bts: {
        panel: 'equity-bts',
        script: "International music group BTS also recognized the power of ownership. Instead of relying only on traditional compensation… they also participated in the long-term value created by what they helped build. Their ownership became worth hundreds of millions of dollars. The lesson isn't about basketball… or social media… or music. It's about recognizing the right ownership opportunity when it comes along. That's exactly why InfluencerHQ was created. Now… let me show you what you can accomplish in just a few minutes.",
    },
};

// FR-05 — Scenario 7/8: 10-minute tier gets all three in order; 5- and
// 2-minute tiers get BTS only.
function getEquityScreensForTier( tierMinutes ) {
    if ( tierMinutes === '10' ) {
        return [ EQUITY_SCREENS.magic, EQUITY_SCREENS.alix, EQUITY_SCREENS.bts ];
    }
    return [ EQUITY_SCREENS.bts ];
}

// FR-06 — competition types explained, no selection requested. 5- and 10-minute
// tiers only; the 2-minute tier skips this section entirely (Scenario 9).
// NOTE: the Private screen's closing/transition line is not yet approved copy
// (the ticket explicitly removed the old "let's choose one" close and flags the
// replacement as pending) — using the given text verbatim, without inventing a
// transition sentence of our own.
const COMPETITION_SCREENS = {
    world: {
        panel: 'competition-world',
        script: "Now let's look at the first way many influencers choose to begin. It's called a World Competition. You and your followers participate together… while competing against other influencers and their communities from around the world. InfluencerHQ already provides the competition format. You don't have to create anything from scratch. Later… inside your Coaching Center… I'll explain exactly how it works and help you decide whether it's the right place for you to begin. Now… let's look at another option.",
    },
    community: {
        panel: 'competition-community',
        script: "Many influencers choose to begin with a Community Competition. It's a simple way to bring together the followers who already support you. Your community stays together… encourages one another… and enjoys participating as a team. Again… InfluencerHQ already provides the competition format. I'll help you get everything set up… step by step. If building your own community first feels right… this may be the perfect place to begin. There's one more option I'd like to show you.",
    },
    private: {
        panel: 'competition-private',
        script: "The third option is called a Private Challenge. It allows you and your followers… to compete with another influencer and their community… someone you already know. Many influencers enjoy Private Challenges because they create friendly competition… encourage engagement… and bring two communities together. Like every competition on InfluencerHQ… the format is already provided. When we continue into your Coaching Center… I'll help you decide whether this is the right place to begin.",
    },
};

function getCompetitionScreensForTier( tierMinutes ) {
    if ( tierMinutes === '2' ) {
        return [];
    }
    return [ COMPETITION_SCREENS.world, COMPETITION_SCREENS.community, COMPETITION_SCREENS.private ];
}

// FR-08 — communication channels. One screen, same for every tier, queued once
// FR-07's identity form succeeds (see the identity submit handler below).
const COMM_CHANNELS_SCREEN = {
    panel: 'comm-channels',
    script: "Before we continue… I'd like to ask one small favor. Please tell me the communication methods you actually use. I'll use them to answer your questions… send reminders… share personalized reports… help you prepare competitions… and continue coaching you as new opportunities become available. Please choose the methods you genuinely use. That way… I'll always know the best way to stay in touch.",
};

// FR-08 — one entry per channel. KakaoTalk/Telegram/WeChat/Zalo's contact-detail
// format is marked "TBD" in the ticket (format/Braze attribute not yet
// confirmed), so those four only require a non-empty value rather than an
// invented strict pattern — Email/SMS/WhatsApp/Line have concrete formats given
// in the ticket, so those get real validation.
// E.164 format (e.g. +66812345678) — shared by every channel whose ticket-given
// contact detail is explicitly a phone number, so the pattern isn't duplicated
// per channel (review nit on PO-3099/#11). Telegram isn't included here even
// though it can carry a phone number in practice — the ticket itself defines
// its format as "numeric ID or @username (TBD)", not phone, so it keeps the
// lenient non-empty check until that's confirmed one way or the other.
const isValidPhoneNumber = ( v ) => /^\+[1-9]\d{7,14}$/.test( v );

const CHANNELS = [
    { key: 'email', validate: ( v ) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( v ) },
    { key: 'kakaotalk', validate: ( v ) => v.length > 0 }, // TBD format
    { key: 'line', validate: ( v ) => /^U[0-9a-fA-F]{32}$/.test( v ) },
    { key: 'sms', validate: isValidPhoneNumber },
    { key: 'telegram', validate: ( v ) => v.length > 0 }, // TBD format — see note above
    { key: 'wechat', validate: ( v ) => v.length > 0 },   // TBD format
    { key: 'whatsapp', validate: isValidPhoneNumber },
    { key: 'zalo', validate: ( v ) => v.length > 0 },     // TBD format
];

// FR-09 — final confirmation, queued once FR-08's comm-channels succeeds.
const FINAL_SCREEN = {
    panel: 'final-continue',
    script: "Perfect. Thank you. I now know how you'd like us to stay connected. Everything is ready. From this point forward… I'll continue guiding you inside your personal Coaching Center. That's where we'll review your options… answer your questions… and take the next steps together. Remember… you don't have to learn everything today. We'll continue exactly where we leave off. When you're ready… Let's Continue.",
};

const cfg = window.AICOACH_SAMI || {};
// inc/gary-proxy.php's routes live under the same ihq/v1 namespace FR-07/09's
// identity endpoints already use — no new PHP localization needed for this.
const GARY_SESSION_URL = cfg.identityRestBase + '/coach/session';
const garyCloseUrl = ( sessionId ) => cfg.identityRestBase + '/coach/' + encodeURIComponent( sessionId ) + '/close';
const garyMessageUrl = ( sessionId ) => cfg.identityRestBase + '/coach/' + encodeURIComponent( sessionId ) + '/message';

// PO-3062 pre-rendered clips — SCREENS panel name -> Gary's own segment key
// (see inc/aicoach-prerender.php's ihq_aicoach_prerender_panel_map(), kept
// in sync with this by hand; the two naming schemes predate each other).
// "competition-world"/"competition-community"/"competition-private" are
// deliberately not mapped — Gary's script has one combined "competitions"
// segment where this page shows three separate panels, and there's no
// single clip that fits all three yet.
const PRERENDERED_PANEL_MAP = {
    'believe-1': 'we_believe_1',
    'believe-2': 'we_believe_2',
    home: 'time_selection',
    'equity-magic': 'magic_johnson',
    'equity-alix': 'alix_earle',
    'equity-bts': 'bts',
};

// FR-16 — single avatar/voice for every language (confirmed with product,
// 2026-09-25), so the only thing that changes per language is which clip
// plays. cfg.prerenderedVideos is now segment_key -> language -> url
// (inc/aicoach-prerender.php's ihq_aicoach_prerender_get_urls()); a language
// with no clip yet for this segment falls back to English, same "missing
// just means not ready" degrade this feature has always had — never no clip
// at all if English exists.
function getPrerenderedUrl( panelKey, locale ) {
    const segmentKey = PRERENDERED_PANEL_MAP[ panelKey ];
    if ( ! segmentKey ) {
        return null;
    }
    const perLanguage = ( cfg.prerenderedVideos || {} )[ segmentKey ] || {};
    // The mirror image of getCaptionScript()'s own check just below: if a
    // translation is edited out of inc/aicoach-segment-translations.php
    // AFTER its clip was already rendered, nothing deletes the now-orphaned
    // clip file or its manifest entry — `wp aicoach prerender` only adds,
    // it doesn't prune. Without this, the orphaned localized clip would
    // still get selected here while getCaptionScript() (correctly, since it
    // has no script text for this locale) falls back to the English
    // caption — a non-English video under an English caption, the same
    // mismatch this ticket fixed, from a third direction. Requiring a
    // script to exist before trusting the clip keeps translated text as the
    // single source of truth both functions key off.
    const perLanguageScripts = ( cfg.segmentScripts || {} )[ segmentKey ] || {};
    const hasScript = 'en' === locale || perLanguageScripts[ locale ];
    return ( hasScript && perLanguage[ locale ] ) || perLanguage.en || null;
}

// NFR-03 — the on-screen caption for a pre-rendered segment, in whatever
// language its clip is actually playing. cfg.segmentScripts (localized from
// inc/aicoach-segment-translations.php) has no 'en' entries — English lives
// in SCREENS/EQUITY_SCREENS's own .script strings — so null here (locale not
// translated for this segment yet) means "use the English .script", the same
// fallback getPrerenderedUrl() already gives the video itself.
function getCaptionScript( panelKey, locale ) {
    const segmentKey = PRERENDERED_PANEL_MAP[ panelKey ];
    if ( ! segmentKey ) {
        return null;
    }
    // A translated script can exist (inc/aicoach-segment-translations.php)
    // before `wp aicoach prerender` has actually rendered its clip on this
    // environment — the two ship independently, translations in code, clips
    // via a separate manual render step. Without this check, that gap
    // reintroduces the exact mismatch this ticket fixed, just flipped: an
    // English clip (getPrerenderedUrl()'s own fallback) under a translated
    // caption. Deriving the caption's language from whichever locale the
    // CLIP actually resolved to — not from whether a translation merely
    // exists — keeps the two locked together.
    const perLanguage = ( cfg.segmentScripts || {} )[ segmentKey ] || {};
    const perLanguageVideos = ( cfg.prerenderedVideos || {} )[ segmentKey ] || {};
    const clipLocale = perLanguageVideos[ locale ] ? locale : 'en';
    return perLanguage[ clipLocale ] || null;
}

// PO-3102 — session persistence (inc/aicoach-progress.php). saveProgress() is
// fire-and-forget as far as any caller is concerned — a failed save must never
// block the coach flow. loadProgress() is only ever awaited once, at start,
// before deciding whether this is a fresh visitor or a resume.
const PROGRESS_URL = cfg.identityRestBase + '/aicoach/progress';

// The backend does a read-merge-write on the saved record (inc/aicoach-
// progress.php's ihq_aicoach_progress_save()), not a real per-field update —
// two saveProgress() calls fired close together (e.g. identityForm's submit
// handler saves the captured identity, then its own showPanel('comm-channels')
// call saves the new stage a moment later) can otherwise reach the server as
// overlapping requests and race: whichever read happens first has its write
// clobbered by the other's merge of now-stale data, silently dropping a field
// that was, from the caller's point of view, already saved. Chaining every
// call onto this promise serializes them client-side — each POST only starts
// once the previous one has settled — which is enough to eliminate the overlap
// without needing a lock on the PHP side for what's a low-frequency, per-visitor
// write.
let progressSaveChain = Promise.resolve();

function saveProgress( partial ) {
    progressSaveChain = progressSaveChain.then( function () {
        return fetch( PROGRESS_URL, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
            body: JSON.stringify( partial ),
        } ).catch( function () {} );
    } );
}

async function loadProgress() {
    try {
        const res = await fetch( PROGRESS_URL, {
            credentials: 'same-origin',
            headers: { 'X-WP-Nonce': cfg.nonce },
        } );
        if ( ! res.ok ) {
            return {};
        }
        const data = await res.json();
        return ( data && typeof data === 'object' ) ? data : {};
    } catch ( error ) {
        return {};
    }
}

const stage = document.getElementById('aicoach-stage');
const avatarWrap = document.getElementById('aicoach-avatar-wrap');
const video = document.getElementById('aicoach-avatar-video');
// Reuses the site-wide header volume control (template-parts/portal-header.php)
// instead of a second, dedicated mute button overlaid on the avatar itself —
// that button currently does nothing but open its own slider on every other
// page, so wiring it to this page's avatar audio is a natural fit rather
// than duplicating a control the visitor already sees top-right.
const headerVolumeBtn = document.getElementById('headerVolumeBtn');
const headerVolumeSlider = document.getElementById('headerVolumeSlider');

function getCaptionEl( panelKey ) {
    return stage.querySelector( '.aicoach-panel[data-panel="' + panelKey + '"] .aicoach-caption' );
}

if ( stage && avatarWrap ) {
    const tiers = stage.querySelectorAll( '.aicoach-tier' );
    let isAnimating = false;

    // FR-13 — language selector, injected into the shared header's own nav row
    // rather than editing template-parts/portal-header.php directly. That file
    // already has a .header-lang-wrap element, but it belongs to a different,
    // unrelated feature (the site-wide ElevenLabs concierge widget — see its git
    // history) with its own mismatched language list; reusing or editing it risks
    // breaking that other feature. This script only ever runs on this one page
    // (see ihq_aicoach_enqueue_coach_flow()'s is_page_template check), so injecting
    // here is safe without any extra page check.
    let currentLocale = detectInitialLocale();
    let askSamiWrap = null; // set once buildAskSami() runs below; selectLocale() toggles its visibility
    let askSamiBtn = null;

    function selectLocale( locale ) {
        if ( locale === currentLocale ) {
            return;
        }
        currentLocale = locale;
        applyLocale( locale );
        saveProgress( { language: locale } ); // PO-3102
        document.querySelectorAll( '.aicoach-lang-option' ).forEach( function ( opt ) {
            const isCurrent = opt.dataset.locale === locale;
            opt.classList.toggle( 'is-current', isCurrent );
            if ( isCurrent ) {
                opt.setAttribute( 'aria-current', 'true' );
            } else {
                opt.removeAttribute( 'aria-current' );
            }
        } );
        // FR-19/PO-3330 — Ask Sami's generated answers are English-only (Gary,
        // 2026-09-28); hide the entry point rather than let a visitor ask a
        // question in a language that can only ever get his fixed fallback line.
        if ( askSamiWrap ) {
            askSamiWrap.hidden = 'en' !== locale;
        }
        restartCurrentClipForLocale( locale ); // FR-14/PO-3105
        // NOTE — scope boundary: the line above restarts a currently-playing
        // PRE-RENDERED clip (PO-3107) in the new language. It does NOT reconnect
        // the LIVE avatar/voice to a fresh Gary session (Scenario 25's "avatar
        // and voice switch" clause, for the one screen — "intro" — that's a real
        // Gary session rather than a pre-rendered clip). That would mean closing
        // the open session, tearing down the connected Anam client, and opening
        // a new one — not done here, tracked as the next increment on top of
        // this rather than folded in silently.
    }

    // FR-14/PO-3105 — "the current screen's avatar video restarts from the
    // beginning in the newly selected language". Delegates to
    // playPrerenderedClip()'s own activeClipRestart (set for whichever clip is
    // currently in flight) so the SAME pending promise runFallback() is already
    // awaiting for this screen keeps working — including its play()-rejection
    // handling, which matters here: without it, an in-flight play() promise
    // from the clip THIS call is replacing can still reject a moment later and
    // wrongly resolve the screen as "failed", skipping straight to the next
    // one instead of playing the just-restarted clip (confirmed live: without
    // playPrerenderedClip()'s generation guard, a same-screen language switch
    // silently lost the restarted clip to its predecessor's stale rejection).
    // No need to touch sequenceIndex or the sequence loop at all. A no-op when
    // the current screen has no pre-rendered clip (a
    // caption-only or competition-name screen), when nothing is actually in
    // flight, or when video.srcObject is set (the live Gary/Anam WebRTC stream
    // for the "intro" screen — reconnecting that is a separate, bigger piece
    // of work, see selectLocale()'s own note above).
    function restartCurrentClipForLocale( locale ) {
        const screen = SCREENS[ sequenceIndex ];
        if ( ! screen || video.srcObject || ! activeClipRestart ) {
            return;
        }
        const newUrl = getPrerenderedUrl( screen.panel, locale );
        // getPrerenderedUrl() falls back to the English clip when the newly
        // selected locale has none of its own for this segment (same "missing
        // just means not ready" degrade as everywhere else) — if that fallback
        // resolves to the EXACT clip already loaded (e.g. switching between two
        // locales that both lack this segment), restarting would just replay
        // the same English clip from 0 for no reason. Comparing against the
        // resolved URL rather than "does this locale have its own entry" also
        // correctly still restarts when the OLD locale had a real clip playing
        // and the new one falls back to English — that genuinely is a
        // different source, not a no-op.
        if ( ! newUrl || newUrl === video.currentSrc ) {
            return;
        }
        activeClipRestart( newUrl );
        // NFR-03 — keep the on-screen caption matching whatever the restarted
        // clip is actually saying, same as the initial-load path in
        // runFallback() above.
        const captionEl = getCaptionEl( screen.panel );
        if ( captionEl ) {
            captionEl.textContent = getCaptionScript( screen.panel, locale ) || screen.script;
        }
    }

    ( function buildLanguageSelector() {
        const headerRow = document.querySelector( '.desktop-header-left-items' );
        if ( ! headerRow ) {
            return;
        }

        const wrap = document.createElement( 'div' );
        wrap.className = 'aicoach-lang-wrap';

        const btn = document.createElement( 'button' );
        btn.type = 'button';
        btn.className = 'aicoach-lang-btn';
        btn.id = 'aicoachLangBtn';
        btn.setAttribute( 'aria-label', 'Select language' );
        btn.setAttribute( 'aria-haspopup', 'true' );
        btn.setAttribute( 'aria-expanded', 'false' );
        btn.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#E6CFA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>';

        const dropdown = document.createElement( 'div' );
        dropdown.className = 'aicoach-lang-dropdown';
        dropdown.id = 'aicoachLangDropdown';

        SUPPORTED_LOCALES.forEach( function ( loc ) {
            const opt = document.createElement( 'button' );
            opt.type = 'button';
            opt.className = 'aicoach-lang-option';
            opt.dataset.locale = loc.code;
            opt.textContent = loc.nativeLabel;
            opt.lang = loc.code;
            if ( loc.code === currentLocale ) {
                opt.classList.add( 'is-current' );
                opt.setAttribute( 'aria-current', 'true' );
            }
            dropdown.appendChild( opt );
        } );

        wrap.appendChild( btn );
        wrap.appendChild( dropdown );
        headerRow.appendChild( wrap );

        btn.addEventListener( 'click', function ( event ) {
            event.stopPropagation();
            // Dejan Arsic (PR #55 review) — this stopPropagation() means a
            // click here never reaches document's unmuteOnFirstInteraction()
            // listener below. If opening (or picking from, see the dropdown
            // handler) this menu is the visitor's very first interaction with
            // the page, audio would otherwise stay muted indefinitely with no
            // other click ever arriving to satisfy the browser's autoplay
            // gate. Calling it explicitly here counts this click too — it's a
            // no-op once already unmuted.
            unmuteOnFirstInteraction();
            const isOpen = wrap.classList.toggle( 'is-open' );
            btn.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
        } );

        document.addEventListener( 'click', function () {
            wrap.classList.remove( 'is-open' );
            btn.setAttribute( 'aria-expanded', 'false' );
        } );

        dropdown.addEventListener( 'click', function ( event ) {
            const option = event.target.closest( '.aicoach-lang-option' );
            if ( ! option ) {
                return;
            }
            event.stopPropagation();
            unmuteOnFirstInteraction(); // see the same note on the open/close button above
            selectLocale( option.dataset.locale );
            wrap.classList.remove( 'is-open' );
            btn.setAttribute( 'aria-expanded', 'false' );
        } );
    }() );

    // FR-19/PO-3330 — "Ask Sami": lets a visitor interrupt the fixed sequence
    // and ask a free-text question, answered by Gary's /message endpoint
    // (inc/gary-proxy.php's ihq_coach_handle_message()), reusing this visit's
    // already-open garySessionId (stays open for the whole flow — see its
    // declaration below). Confirmed with Gary 2026-09-28: generated answers
    // are English-only for now, so this entry point is hidden outside the
    // 'en' locale (selectLocale() above) rather than shown for a feature that
    // would just return his fixed fallback line.
    //
    // Text-only for this MVP — deliberately does NOT play back say.audio or
    // reconnect the avatar for say.video. Confirmed live (2026-09-28):
    // say.audio is not a playable URL but an object ({ id, url, format,
    // expires_at, ... }) whose "url" (e.g. "/coach/v1/audio/{id}") is a path
    // on Gary's own API host, HMAC-signed the same way every other
    // ihq_coach_request() call is — the visitor's browser can't fetch it
    // directly, and nothing in inc/gary-proxy.php proxies it live today
    // (ihq_coach_download() is a build-time, stream-to-disk tool used by the
    // prerender scripts, not a REST route). Playing the answer back through
    // Sami (live avatar or plain audio) needs that proxy route built first —
    // tracked as a real follow-up, not silently attempted here. The main
    // avatar video is still paused while this panel is open, independent of
    // that — purely so the visitor isn't reading/typing while the sequence
    // advances underneath them.
    ( function buildAskSami() {
        const wrap = document.createElement( 'div' );
        wrap.className = 'aicoach-ask-wrap';
        wrap.hidden = 'en' !== currentLocale;

        const btn = document.createElement( 'button' );
        btn.type = 'button';
        btn.className = 'aicoach-ask-btn';
        btn.disabled = true; // enabled once start() below has a real garySessionId
        btn.setAttribute( 'aria-haspopup', 'true' );
        btn.setAttribute( 'aria-expanded', 'false' );
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg><span>Ask Sami</span>';

        const panel = document.createElement( 'div' );
        panel.className = 'aicoach-ask-panel';

        const closeBtn = document.createElement( 'button' );
        closeBtn.type = 'button';
        closeBtn.className = 'aicoach-ask-close';
        closeBtn.setAttribute( 'aria-label', 'Close' );
        closeBtn.textContent = '×';

        const answerEl = document.createElement( 'p' );
        answerEl.className = 'aicoach-ask-answer';
        answerEl.setAttribute( 'aria-live', 'polite' );

        const form = document.createElement( 'form' );
        form.className = 'aicoach-ask-form';

        const input = document.createElement( 'input' );
        input.type = 'text';
        input.className = 'aicoach-ask-input';
        input.maxLength = 500;
        input.autocomplete = 'off';
        input.placeholder = 'Ask Sami a question…';
        input.setAttribute( 'aria-label', 'Ask Sami a question' ); // the placeholder alone disappears once the visitor types, leaving no accessible name

        const submitBtn = document.createElement( 'button' );
        submitBtn.type = 'submit';
        submitBtn.className = 'aicoach-ask-submit';
        submitBtn.textContent = 'Ask';

        const errorEl = document.createElement( 'p' );
        errorEl.className = 'aicoach-ask-error';
        errorEl.setAttribute( 'role', 'alert' );

        form.appendChild( input );
        form.appendChild( submitBtn );
        panel.appendChild( closeBtn );
        panel.appendChild( answerEl );
        panel.appendChild( form );
        panel.appendChild( errorEl );
        wrap.appendChild( btn );
        wrap.appendChild( panel );
        // Sibling of .aicoach-stage, not inside it — .aicoach-stage's own click
        // listener (tap-to-skip) would otherwise treat every click in here as
        // "advance to the next screen".
        avatarWrap.insertAdjacentElement( 'afterend', wrap );

        askSamiWrap = wrap;
        askSamiBtn = btn;

        let wasPlaying = false; // the main avatar video's state before the panel paused it, restored on close

        function openPanel() {
            wrap.classList.add( 'is-open' );
            btn.setAttribute( 'aria-expanded', 'true' );
            wasPlaying = ! video.paused;
            video.pause();
            // PO-3346 — pausing the video alone left the sequence's own
            // dwell/safety timers running, so it could advance to a later
            // screen (or start a new clip) while this panel sat open on top
            // of it. Doesn't cover the live intro's own wait — see the note
            // on pausableSequenceTimers' declaration.
            pauseSequenceTimers();
            window.setTimeout( function () { input.focus(); }, 0 );
        }

        function closePanel() {
            wrap.classList.remove( 'is-open' );
            btn.setAttribute( 'aria-expanded', 'false' );
            resumeSequenceTimers();
            if ( wasPlaying ) {
                video.play().catch( function () {} );
            }
        }

        btn.addEventListener( 'click', function ( event ) {
            event.stopPropagation();
            unmuteOnFirstInteraction(); // see the same note on the language button above
            if ( wrap.classList.contains( 'is-open' ) ) {
                closePanel();
            } else {
                openPanel();
            }
        } );

        closeBtn.addEventListener( 'click', function ( event ) {
            event.stopPropagation();
            closePanel();
        } );

        panel.addEventListener( 'click', function ( event ) {
            event.stopPropagation(); // typing/clicking inside the panel must not trigger the document listener below
        } );

        document.addEventListener( 'click', function () {
            if ( wrap.classList.contains( 'is-open' ) ) {
                closePanel();
            }
        } );

        form.addEventListener( 'submit', async function ( event ) {
            event.preventDefault();
            const text = input.value.trim();
            if ( ! text || ! garySessionId ) {
                return;
            }
            errorEl.textContent = '';
            answerEl.textContent = '';
            input.disabled = true;
            submitBtn.disabled = true;
            try {
                const res = await fetch( garyMessageUrl( garySessionId ), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
                    body: JSON.stringify( { text: text } ),
                } );
                // Same "read as text first" reasoning as openGarySession() — a
                // 502 from Cloudflare/PHP-FPM is an HTML page, not JSON.
                const rawBody = await res.text();
                let data;
                try {
                    data = JSON.parse( rawBody );
                } catch ( parseError ) {
                    throw new Error( 'Ask Sami returned a non-JSON response (HTTP ' + res.status + ').' );
                }
                if ( ! res.ok || ! data.say?.text ) {
                    throw new Error( data.error || 'Ask Sami request failed.' );
                }
                answerEl.textContent = data.say.text;
                input.value = '';
            } catch ( error ) {
                console.warn( '[aicoach] Ask Sami request failed:', error );
                errorEl.textContent = 'Something went wrong — please try again.';
            } finally {
                input.disabled = false;
                submitBtn.disabled = false;
                input.focus();
            }
        } );
    }() );

    // Apply the detected locale to the DOM now that the selector (and its
    // is-current marking, set from currentLocale above) exists. A no-op for
    // English/untranslated locales today (t() falls back to I18N_EN either
    // way) but keeps behavior correct once real translations land.
    applyLocale( currentLocale );

    function syncSelected() {
        tiers.forEach( function ( tier ) {
            const input = tier.querySelector( '.aicoach-tier-check' );
            tier.classList.toggle( 'is-selected', Boolean( input && input.checked ) );
        } );
    }

    function getActivePanel() {
        return stage.querySelector( '.aicoach-panel.is-active' );
    }

    let pendingPanelKey = null; // a showPanel() call that arrived mid-transition; applied once the current one settles

    // NFR-01 — panels are toggled via opacity/visibility, not display:none (that's
    // what makes the fade transition possible), so the browser's native
    // loading="lazy" never kicks in for images inside them (they're still laid out
    // in the viewport). Heavier images (equity examples) use data-src instead and
    // get hydrated into a real src here, the first time their panel is actually
    // requested — not on initial page load.
    function hydratePanelImages( panel ) {
        panel.querySelectorAll( 'img[data-src]' ).forEach( function ( img ) {
            img.src = img.getAttribute( 'data-src' );
            img.removeAttribute( 'data-src' );
        } );
    }

    // Callers that need to know the requested panel is actually visible
    // (not just "requested") — currently just playPrerenderedClip() below —
    // await showPanel()'s returned promise instead of guessing a fixed delay.
    // Every other call site still just fires it and moves on, which is fine:
    // a Promise nobody awaits behaves exactly like the old `undefined` return.
    let panelActivationWaiters = []; // [{ key, resolve }]

    function resolvePanelWaiters( key ) {
        panelActivationWaiters = panelActivationWaiters.filter( ( waiter ) => {
            if ( waiter.key !== key ) {
                return true;
            }
            waiter.resolve();
            return false;
        } );
    }

    function showPanel( panelKey ) {
        const next = stage.querySelector( '.aicoach-panel[data-panel="' + panelKey + '"]' );
        if ( ! next ) {
            return Promise.resolve();
        }

        // PO-3102 — every real panel transition is "current stage" for Scenario
        // 20's continuous save, not just the 3 form-submit checkpoints.
        saveProgress( { stage: panelKey } );

        hydratePanelImages( next );

        if ( isAnimating ) {
            // Don't silently drop this — e.g. FR-08's screen gets queued right after
            // FR-07's own showPanel('identity') call, well within the 800ms fade
            // window on a fast/automated submit. Remember the latest request and
            // apply it once the in-flight transition finishes.
            if ( pendingPanelKey && pendingPanelKey !== panelKey ) {
                // The previously queued key is about to be overwritten and will
                // never show — resolve its waiters now so an awaiting caller
                // doesn't hang forever, same "latest wins" trade-off the
                // fire-and-forget callers already silently accept.
                resolvePanelWaiters( pendingPanelKey );
            }
            pendingPanelKey = panelKey;
            return new Promise( ( resolve ) => {
                panelActivationWaiters.push( { key: panelKey, resolve } );
            } );
        }

        const current = getActivePanel();
        if ( ! current || next === current ) {
            return Promise.resolve();
        }

        isAnimating = true;
        stage.style.minHeight = current.offsetHeight + 'px';

        current.classList.remove( 'is-active' );
        current.setAttribute( 'aria-hidden', 'true' );

        return new Promise( ( resolve ) => {
            window.setTimeout( function () {
                next.classList.add( 'is-active' );
                next.setAttribute( 'aria-hidden', 'false' );
                stage.style.minHeight = next.offsetHeight + 'px';
                resolvePanelWaiters( panelKey );
                resolve();

                window.setTimeout( function () {
                    stage.style.minHeight = '';
                    isAnimating = false;

                    if ( pendingPanelKey && pendingPanelKey !== panelKey ) {
                        const queued = pendingPanelKey;
                        pendingPanelKey = null;
                        showPanel( queued );
                    } else {
                        pendingPanelKey = null;
                    }
                }, FADE_MS );
            }, FADE_MS );
        } );
    }

    let sequenceIndex = 0;
    let sequenceFinished = false;
    let skipCurrent = null; // set while a screen's line is in flight; a tap calls this to advance early
    let activeClipRestart = null; // set while a pre-rendered clip is in flight — FR-14/PO-3105's selectLocale() calls this to swap languages mid-playback

    // FR-03 — timestamp + chosen tier, captured the moment the visitor confirms a
    // selection. This is only the starting mark; FR-17 (time-remaining check) is a
    // separate story and owns the actual countdown/prompt logic built on top of it.
    // Deliberately in-memory only, not persisted — cross-session resume is FR-11's
    // job (Luna), not this one's.
    let elapsedStartedAt = null;
    let selectedTierMinutes = null;
    let tierConfirmed = false; // FR-03 confirm is one-time; ignore further tier clicks after that
    let activeClient = null;   // the connected Anam client (via Gary's session_token), once one exists
    let garySessionId = null;  // this visit's Gary coach session id, for the close() call on completion
    let avatarIsLive = false;  // true once Gary's session connected and video started — keeps the avatar
                                // visible (not the idle portrait) through runFallback()'s static captions

    // Where finishSequence() navigates once the current SCREENS queue is fully
    // consumed — this changes as later FRs queue more content after their own
    // predecessor's success handler runs. FR-07 is the first destination (no
    // spoken script of its own); FR-08's success handler clears this back to
    // null since nothing is built after comm-channels yet. Without this, calling
    // finishSequence() a second time (after FR-08's one queued screen finishes)
    // would incorrectly snap back to 'identity' instead of just stopping.
    let sequenceEndDestination = 'identity';

    function finishSequence() {
        if ( sequenceFinished ) {
            return;
        }
        sequenceFinished = true;
        // No coach script exists for identity capture (unlike every screen
        // before it) or anything after it yet, so those steps aren't part of
        // SCREENS — they're plain showPanel() destinations reached once
        // whatever's currently queued in SCREENS runs out. Which
        // destination that is changes as later stories queue more content (see
        // sequenceEndDestination); null means nothing built yet, just stop.
        if ( sequenceEndDestination ) {
            showPanel( sequenceEndDestination );
        }
    }

    // Use click so a pre-checked tier (e.g. 2 minutes) still opens its story. A click
    // is also the FR-03 "confirm" action: it ends the coach's time-selection line
    // early if she's still mid-sentence (same tap-to-advance mechanism as the We
    // Believe screens), then queues that tier's FR-05 equity examples and (for
    // 5/10-minute tiers) FR-06 competition-type screens onto SCREENS — runFallback's
    // own loop picks them up and shows them in the usual way. If
    // that loop already finished by the time the visitor clicks (they took a
    // moment to decide), nothing is left running to notice the new screens, so
    // resume it explicitly.
    tiers.forEach( function ( tier ) {
        const input = tier.querySelector( '.aicoach-tier-check' );
        if ( ! input ) {
            return;
        }

        tier.addEventListener( 'click', function () {
            if ( tierConfirmed ) {
                return;
            }
            tierConfirmed = true;

            input.checked = true;
            syncSelected();
            const panelKey = input.getAttribute( 'data-panel' );
            if ( ! panelKey ) {
                return;
            }

            elapsedStartedAt = Date.now();
            selectedTierMinutes = panelKey;
            saveProgress( { tier: panelKey } ); // PO-3102
            scheduleTimeRemainingCheck( panelKey );
            const loopAlreadyExited = sequenceFinished;
            SCREENS.push( ...getEquityScreensForTier( panelKey ), ...getCompetitionScreensForTier( panelKey ) );

            if ( skipCurrent ) {
                skipCurrent();
            }

            if ( loopAlreadyExited ) {
                sequenceFinished = false;
                runFallback( avatarIsLive );
            }
        } );
    } );

    syncSelected();

    // Text has nothing to sync against here, so each screen is shown in full
    // and paced by an estimated reading dwell rather than word-by-word reveal.
    // Resumes from sequenceIndex, so a mid-sequence connection drop (or a late
    // tier click after the queue already ran out) picks up wherever playback
    // left off instead of restarting from the intro.
    //
    // keepAvatarLive: true when a real Gary session is connected and the video
    // should stay visible while these static captions play (the normal path,
    // now that Gary has no way to recite this copy itself — see the top-of-file
    // note); false/omitted for a genuine connection failure, which reverts the
    // avatar to the idle portrait like before.
    //
    // fallbackRunning guards against a second concurrent loop racing the same
    // shared sequenceIndex/skipCurrent — Anam's VIDEO_PLAY_STARTED has been
    // observed to fire more than once for a single session (a WebRTC
    // renegotiation, not a real second connection), which would otherwise call
    // this twice and advance two screens per tap instead of one.
    let fallbackRunning = false;

    // FR-02 — narration timing OR visitor tap/click; the stage click handler
    // below calls skipCurrent() to advance early. Shared by runFallback()'s own
    // loop and by the VIDEO_PLAY_STARTED handler, which needs the exact same
    // dwell-or-skip behavior for Gary's opening line before handing off to
    // runFallback() for the rest of the screens.
    function waitForReadOrSkip() {
        return new Promise( ( resolve ) => {
            let settled = false;
            let timer;
            const finish = () => {
                if ( settled ) {
                    return;
                }
                settled = true;
                skipCurrent = null;
                timer.cancel(); // PO-3346 — no-op if it already fired to get here
                resolve();
            };
            skipCurrent = finish;
            timer = createPausableTimeout( finish, FALLBACK_READ_MS );
        } );
    }

    // Live-speech-only counterpart to waitForReadOrSkip(): a fixed dwell has
    // nothing to sync against for the static fallback screens (no audio at
    // all), but Gary's real spoken intro line does — MESSAGE_HISTORY_UPDATED
    // fires once the avatar's full utterance is complete. Measured live:
    // talk() itself resolves almost instantly (it just queues the request),
    // then MESSAGE_HISTORY_UPDATED can take anywhere from ~9s to ~19s
    // depending on reply length — a fixed FALLBACK_READ_MS (9000ms) either
    // cuts her off mid-sentence or leaves the caption sitting long after
    // she's actually finished, which is exactly the "out of sync" feedback
    // this replaces. capMs is a safety net only, in case the event never
    // arrives for some reason (dropped connection, SDK quirk).
    function waitForSpeechOrSkip( client, capMs ) {
        return new Promise( ( resolve ) => {
            let settled = false;
            const finish = () => {
                if ( settled ) {
                    return;
                }
                settled = true;
                skipCurrent = null;
                client.removeListener( AnamEvent.MESSAGE_HISTORY_UPDATED, finish );
                resolve();
            };
            client.addListener( AnamEvent.MESSAGE_HISTORY_UPDATED, finish );
            skipCurrent = finish;
            window.setTimeout( finish, capMs );
        } );
    }

    // Plays a pre-rendered clip (inc/aicoach-prerender.php) in the same
    // <video> element the live avatar uses, resolving true on the clip's
    // natural 'ended' event (or a manual skip) instead of a fixed dwell —
    // the clip's own length already matches its audio exactly, nothing to
    // estimate. Resolves false on a playback error; the caller is
    // responsible for falling back to the ordinary caption dwell in that
    // case (this function only plays video, it doesn't own the caption's
    // timing on failure).
    function playPrerenderedClip( url ) {
        return new Promise( ( resolve ) => {
            let settled = false;
            // FR-14/PO-3105 — bumped by load() on every (re)start, including a
            // restartCurrentClipForLocale() swap mid-playback. A play() promise
            // rejection captures the generation it belongs to when it was issued;
            // if a restart has since loaded a newer clip, that rejection is stale
            // (the interrupted old attempt failing, not the new one) and must be
            // ignored instead of wrongly finishing this screen as "failed" out
            // from under the clip the visitor is now actually watching.
            let generation = 0;
            let safetyTimer = null;
            const finish = ( ok ) => {
                if ( settled ) {
                    return;
                }
                settled = true;
                skipCurrent = null;
                activeClipRestart = null;
                if ( safetyTimer ) {
                    safetyTimer.cancel();
                }
                video.removeEventListener( 'ended', onEnded );
                video.removeEventListener( 'error', onError );
                video.pause(); // a manual skip or the safety cap would otherwise leave it playing into the next screen
                resolve( ok );
            };
            const onEnded = () => finish( true );
            const onError = () => {
                console.warn( '[aicoach] prerendered clip failed to play, falling back to caption dwell:', url );
                finish( false );
            };
            const load = ( clipUrl ) => {
                const myGeneration = ++generation;
                // srcObject (the live WebRTC stream, if any) takes priority over
                // src in the video element — clear it first or a plain MP4 src
                // silently never plays.
                video.srcObject = null;
                video.currentTime = 0;
                video.src = clipUrl;
                avatarWrap.dataset.status = 'live'; // same CSS state that reveals .aicoach-avatar-video over the static portrait
                video.play().catch( () => {
                    if ( myGeneration === generation ) {
                        onError();
                    }
                } );
                // FR-14/PO-3105 — a restart mid-playback re-arms this cap from 0
                // too. Without that, a language switch shortly before the
                // original 60s deadline would have the STALE timer fire a few
                // seconds into the restarted clip, cutting it off early even
                // though 'ended' hasn't happened yet for it.
                if ( safetyTimer ) {
                    safetyTimer.cancel();
                }
                // PO-3346 — pause-aware: safety cap — 'ended' should always fire first
                safetyTimer = createPausableTimeout( () => finish( true ), 60000 );
            };
            video.addEventListener( 'ended', onEnded );
            video.addEventListener( 'error', onError );
            load( url );
            skipCurrent = () => finish( true ); // a visitor tap is a normal advance, not a failure
            // FR-14/PO-3105 — selectLocale() calls this (via
            // restartCurrentClipForLocale()) to swap the currently-playing clip's
            // language without disturbing this same pending promise; 'ended' for
            // whichever clip is loaded when it naturally finishes still resolves
            // it exactly as before.
            activeClipRestart = load;
        } );
    }

    async function runFallback( keepAvatarLive ) {
        if ( fallbackRunning ) {
            return;
        }
        fallbackRunning = true;
        if ( ! keepAvatarLive ) {
            avatarWrap.dataset.status = 'idle';
        }
        for ( ; sequenceIndex < SCREENS.length; sequenceIndex++ ) {
            const screen = SCREENS[ sequenceIndex ];
            const panelReady = showPanel( screen.panel );
            const captionEl = getCaptionEl( screen.panel );
            if ( captionEl ) {
                // NFR-03 — best-effort immediate text so the caption isn't blank
                // during the fade; re-set below once currentLocale is final for
                // this screen, same reasoning as the clip URL re-resolve just
                // after panelReady.
                captionEl.textContent = getCaptionScript( screen.panel, currentLocale ) || screen.script;
            }
            // PO-3062 — an approved segment with a pre-rendered clip plays it
            // (real voice + lip-sync, dwell = the clip's own length); every
            // other screen keeps the original caption-only fixed dwell.
            if ( getPrerenderedUrl( screen.panel, currentLocale ) ) {
                // Wait for this exact panel to actually be the active one —
                // not just a fixed FADE_MS guess, which breaks if showPanel()
                // had to queue behind another in-flight transition (it can
                // take longer than one FADE_MS in that case; see showPanel()).
                await panelReady;
                // FR-14/PO-3105 — re-resolve against currentLocale rather than
                // reusing a URL captured before this await: a visitor who picks
                // a different language during showPanel()'s ~800ms fade (before
                // playPrerenderedClip() below has even started, so
                // restartCurrentClipForLocale() has nothing in flight yet to
                // restart) would otherwise still hear this screen start in the
                // language they just left.
                const clipUrl = getPrerenderedUrl( screen.panel, currentLocale );
                // NFR-03 — keep the caption in step with whichever clip just got
                // (re-)resolved above, for the same reason.
                if ( captionEl ) {
                    captionEl.textContent = getCaptionScript( screen.panel, currentLocale ) || screen.script;
                }
                const played = clipUrl && await playPrerenderedClip( clipUrl );
                if ( ! played ) {
                    await waitForReadOrSkip();
                }
            } else {
                await waitForReadOrSkip();
            }
            if ( sequenceFinished ) {
                fallbackRunning = false;
                return; // a fresh runFallback() call elsewhere already took over
            }
        }
        fallbackRunning = false;
        finishSequence();
    }

    // Tap/click to skip ahead — FR-02's "narration timing or visitor tap/click",
    // covers the We Believe screens and the FR-03 time-selection narration (not
    // the FR-01 intro). Tier clicks have their own handler above and are excluded
    // here so this listener never double-handles them.
    stage.addEventListener( 'click', function ( event ) {
        if ( isAnimating ) {
            return; // avoid desyncing the caption/panel if tapped mid-fade
        }
        const current = SCREENS[ sequenceIndex ];
        if ( ! current || current.panel === 'intro' ) {
            return;
        }
        if ( event.target.closest( '.aicoach-tier' ) ) {
            return;
        }
        if ( skipCurrent ) {
            skipCurrent();
        }
    } );

    // Click toggles mute (also opens/closes the slider — see portal-header.php's
    // own handler for that part, unchanged); dragging the slider sets a level
    // directly and drives mute from it, same as any standard volume control.
    headerVolumeBtn?.addEventListener( 'click', function () {
        video.muted = ! video.muted;
    } );
    headerVolumeSlider?.addEventListener( 'input', function () {
        const level = Number( headerVolumeSlider.value ) / 100;
        video.volume = level;
        video.muted = 0 === level;
    } );

    // Browsers block autoplaying audio until the visitor has interacted with
    // the page at least once — there's no way around that for a page that
    // starts talking on arrival with no required tap. The first click/tap/
    // keypress anywhere unmutes it, same as the "tap to unmute" pattern used
    // elsewhere for autoplaying video with sound; the header volume control
    // above is for explicitly muting/adjusting afterward.
    function unmuteOnFirstInteraction() {
        document.removeEventListener( 'click', unmuteOnFirstInteraction );
        document.removeEventListener( 'keydown', unmuteOnFirstInteraction );
        if ( ! video.muted ) {
            return;
        }
        video.muted = false;
    }
    document.addEventListener( 'click', unmuteOnFirstInteraction );
    document.addEventListener( 'keydown', unmuteOnFirstInteraction );

    // FR-07 — identity capture form (First Name, Last Name, Username). No coach
    // narration exists for this screen (no approved script, unlike every prior
    // one), so it's plain form logic: CONTINUE is gated only on all three
    // fields being non-empty (Scenario 10); the username-uniqueness check runs
    // on blur or on submit (Scenario 11) and blocks proceeding if taken.
    //
    // The uniqueness-check endpoint is owned by BE (not built as of this
    // story) — checkUsernameAvailability's contract (GET .../username-available
    // ?username=, expects { available: bool }) is this FE's assumption, and it
    // fails open (treats a network/404 error as "available") purely so this
    // form stays usable end-to-end before BE ships the real endpoint. Confirm
    // the actual contract with BE and remove the fail-open once it's live.
    const identityForm = document.getElementById( 'aicoach-identity-form' );
    const firstNameInput = document.getElementById( 'aicoach-first-name' );
    const lastNameInput = document.getElementById( 'aicoach-last-name' );
    const usernameInput = document.getElementById( 'aicoach-username' );
    const usernameError = document.getElementById( 'aicoach-username-error' );
    const identityContinueBtn = document.getElementById( 'aicoach-form-continue' );

    let capturedIdentity = null;      // { firstName, lastName, username } once FR-07 completes; FR-08/09 read this next
    let usernameCheckedValue = null;  // last username value a check actually ran against
    let usernameAvailable = null;     // null = needs a (re)check, true/false = last check's result
    let usernameCheckToken = 0;       // guards a stale async response from overwriting a newer one

    function identityFieldsFilled() {
        return Boolean(
            firstNameInput?.value.trim() &&
            lastNameInput?.value.trim() &&
            usernameInput?.value.trim()
        );
    }

    function updateIdentityContinueState() {
        if ( identityContinueBtn ) {
            identityContinueBtn.disabled = ! identityFieldsFilled();
        }
    }

    async function checkUsernameAvailability( username ) {
        try {
            const res = await fetch(
                cfg.identityRestBase + '/username-available?username=' + encodeURIComponent( username ),
                { headers: { 'X-WP-Nonce': cfg.nonce } }
            );
            if ( ! res.ok ) {
                throw new Error( 'username-available returned ' + res.status );
            }
            const data = await res.json();
            return Boolean( data.available );
        } catch ( error ) {
            console.warn( '[aicoach] username-available check failed, assuming available (BE endpoint not live yet):', error );
            return true;
        }
    }

    async function runUsernameCheck() {
        const value = usernameInput.value.trim();
        if ( ! value ) {
            return;
        }
        if ( value === usernameCheckedValue && usernameAvailable !== null ) {
            return; // already have a fresh answer for this exact value
        }

        const token = ++usernameCheckToken;
        const available = await checkUsernameAvailability( value );
        if ( token !== usernameCheckToken ) {
            return; // a newer check superseded this one
        }

        usernameCheckedValue = value;
        usernameAvailable = available;
        usernameInput.classList.toggle( 'is-invalid', ! available );
        usernameError.textContent = available ? '' : ( cfg.i18n?.usernameTaken || 'That username is already taken.' );
    }

    if ( identityForm ) {
        [ firstNameInput, lastNameInput ].forEach( function ( input ) {
            input?.addEventListener( 'input', updateIdentityContinueState );
        } );

        usernameInput?.addEventListener( 'input', function () {
            usernameAvailable = null; // the typed value no longer matches what was last checked
            usernameError.textContent = '';
            usernameInput.classList.remove( 'is-invalid' );
            updateIdentityContinueState();
        } );
        usernameInput?.addEventListener( 'blur', runUsernameCheck );

        identityForm.addEventListener( 'submit', async function ( event ) {
            event.preventDefault();
            if ( ! identityFieldsFilled() ) {
                return;
            }
            await runUsernameCheck();
            if ( usernameAvailable === false ) {
                return; // inline error already shown by runUsernameCheck
            }
            capturedIdentity = {
                firstName: firstNameInput.value.trim(),
                lastName: lastNameInput.value.trim(),
                username: usernameInput.value.trim(),
            };
            saveProgress( { identity: capturedIdentity } ); // PO-3102
            identityContinueBtn.disabled = true;
            identityContinueBtn.textContent = cfg.i18n?.identitySaved || 'Saved';

            // FR-08 — queue the comm-channels screen the same way FR-03's tier
            // confirm queues equity/competition screens: push onto SCREENS and
            // resume whichever runner was active (the loop exited when we first
            // reached 'identity', so it needs an explicit resume here, same as a
            // late tier click in PO-3096. sequenceFinished is always true at this
            // point in real usage (finishSequence() is the only way to reach the
            // identity panel at all, and it sets this first) — the loopAlreadyExited
            // check just keeps this self-consistent with the tier-click pattern
            // rather than relying on that invariant implicitly.
            const loopAlreadyExited = sequenceFinished;
            SCREENS.push( COMM_CHANNELS_SCREEN );
            // Nothing is built after comm-channels yet (that's FR-09) — clear the
            // destination so finishSequence(), once comm-channels' single queued
            // screen finishes, just stops instead of bouncing back to 'identity'.
            sequenceEndDestination = null;
            if ( loopAlreadyExited ) {
                sequenceFinished = false;
                runFallback( avatarIsLive );
            }
        } );
    }

    // FR-08 — communication channels form. Checking a channel reveals its input
    // field (Scenario 14); CONTINUE is gated on at least one channel checked and
    // every checked channel's field passing its format check (Scenario 15).
    const channelsForm = document.getElementById( 'aicoach-channels-form' );
    const channelsHint = document.getElementById( 'aicoach-channels-hint' );
    const channelsContinueBtn = document.getElementById( 'aicoach-channels-continue' );

    let capturedChannels = null; // [{ channel, value }] once FR-08 completes; FR-09 reads this next

    function getChannelParts( key ) {
        const row = channelsForm?.querySelector( '.aicoach-channel[data-channel="' + key + '"]' );
        return {
            row: row,
            checkbox: row?.querySelector( '.aicoach-channel-check' ),
            field: row?.querySelector( '.aicoach-channel-field' ),
            input: row?.querySelector( '.aicoach-channel-input' ),
            error: row?.querySelector( '.aicoach-channel-error' ),
        };
    }

    function checkedChannels() {
        return CHANNELS.map( function ( ch ) {
            return { ch: ch, parts: getChannelParts( ch.key ) };
        } ).filter( function ( entry ) {
            return Boolean( entry.parts.checkbox?.checked );
        } );
    }

    function updateChannelsContinueState() {
        const checked = checkedChannels();
        const valid = checked.length > 0 && checked.every( function ( entry ) {
            return entry.ch.validate( entry.parts.input.value.trim() );
        } );
        if ( channelsContinueBtn ) {
            channelsContinueBtn.disabled = ! valid;
        }
        if ( channelsHint ) {
            channelsHint.classList.toggle( 'is-error', checked.length === 0 );
        }
    }

    if ( channelsForm ) {
        CHANNELS.forEach( function ( ch ) {
            const { checkbox, field, input, error } = getChannelParts( ch.key );
            if ( ! checkbox || ! field || ! input ) {
                return;
            }

            checkbox.addEventListener( 'change', function () {
                field.hidden = ! checkbox.checked;
                if ( ! checkbox.checked ) {
                    input.value = '';
                    input.classList.remove( 'is-invalid' );
                    error.textContent = '';
                }
                updateChannelsContinueState();
            } );

            input.addEventListener( 'input', function () {
                input.classList.remove( 'is-invalid' );
                error.textContent = '';
                updateChannelsContinueState();
            } );

            input.addEventListener( 'blur', function () {
                const value = input.value.trim();
                if ( checkbox.checked && value && ! ch.validate( value ) ) {
                    input.classList.add( 'is-invalid' );
                    error.textContent = cfg.i18n?.channelInvalid || 'Please check this value and try again.';
                }
            } );
        } );

        channelsForm.addEventListener( 'submit', function ( event ) {
            event.preventDefault();
            const checked = checkedChannels();
            if ( checked.length === 0 ) {
                channelsHint?.classList.add( 'is-error' );
                return;
            }

            let allValid = true;
            checked.forEach( function ( entry ) {
                const value = entry.parts.input.value.trim();
                if ( ! entry.ch.validate( value ) ) {
                    allValid = false;
                    entry.parts.input.classList.add( 'is-invalid' );
                    entry.parts.error.textContent = cfg.i18n?.channelInvalid || 'Please check this value and try again.';
                }
            } );
            if ( ! allValid ) {
                return;
            }

            capturedChannels = checked.map( function ( entry ) {
                return { channel: entry.ch.key, value: entry.parts.input.value.trim() };
            } );
            saveProgress( { channels: capturedChannels } ); // PO-3102
            channelsContinueBtn.disabled = true;
            channelsContinueBtn.textContent = cfg.i18n?.identitySaved || 'Saved';

            // FR-09 — queue the final confirmation + account-creation screen, same
            // resume pattern as FR-07 queuing FR-08. sequenceEndDestination stays
            // null: nothing to navigate to on completing this screen's own
            // narration — the actual "next step" is the portal redirect the
            // final-continue button triggers, not another panel.
            const loopAlreadyExited = sequenceFinished;
            SCREENS.push( FINAL_SCREEN );
            if ( loopAlreadyExited ) {
                sequenceFinished = false;
                runFallback( avatarIsLive );
            }
        } );
    }

    // FR-09 / PO-3257 — account creation + portal transfer. Luna and this
    // button share window.ihqCoachEvents.register(); redirect:false so we can
    // close the Gary session before navigating. No fail-open: a missing
    // event module or a failed create must surface, not fake a portal session.
    const finalContinueBtn = document.getElementById( 'aicoach-final-continue' );
    const finalError = document.getElementById( 'aicoach-final-error' );

    finalContinueBtn?.addEventListener( 'click', async function () {
        if ( typeof window.ihqCoachEvents?.register !== 'function' ) {
            if ( finalError ) {
                finalError.textContent = cfg.i18n?.accountCreateErr || 'Something went wrong creating your account. Please try again.';
            }
            return;
        }

        finalContinueBtn.disabled = true;
        if ( finalError ) {
            finalError.textContent = '';
        }

        try {
            // PO-3102 — every prior showPanel()/identity/channels save is chained
            // onto this same promise. Without waiting for it here, a still-in-flight
            // save (e.g. this very panel's own "stage: final-continue" save, fired
            // moments ago) could reach the server AFTER account creation already
            // cleared the progress record, recreating an orphaned row for a visitor
            // who's already a real WP user and will never return to this flow.
            await progressSaveChain;
            const captured = capturedIdentity || {};
            const data = await window.ihqCoachEvents.register( {
                firstName: captured.firstName,
                lastName: captured.lastName,
                username: captured.username,
                channels: capturedChannels || undefined,
                language: currentLocale,
                redirect: false,
            } );
            if ( ! data || ! data.success || ! data.redirectUrl ) {
                throw new Error( ( data && data.error ) || 'create-account failed' );
            }

            // Best-effort cleanup, matches Gary's documented session lifecycle —
            // not awaited so it never delays the redirect the visitor is waiting on.
            if ( garySessionId ) {
                fetch( garyCloseUrl( garySessionId ), { method: 'POST', headers: { 'X-WP-Nonce': cfg.nonce } } )
                    .catch( function () {} );
            }

            window.location.href = data.redirectUrl;
        } catch ( error ) {
            console.warn( '[aicoach] account creation failed:', error );
            if ( finalError ) {
                finalError.textContent = cfg.i18n?.accountCreateErr || 'Something went wrong creating your account. Please try again.';
            }
            finalContinueBtn.disabled = false;
        }
    } );

    // FR-17 — time-remaining check (Scenario 29/30). Overlays whichever screen
    // is currently showing (it isn't one of the SCREENS/aicoach-panel entries,
    // since it can interrupt any of them) once the visitor has used up
    // TIME_REMAINING_THRESHOLD_RATIO of their selected tier. Fires once per
    // session (Scenario 30's "does not fire again"); if the visitor has
    // already completed registration by then, the page has already navigated
    // away to the portal (PO-3100's redirect) and this timer is moot — no
    // extra guard needed for that case.
    //
    // "No" is meant to trigger FR-18's appointment scheduling (PO-3109, a
    // separate story, not built yet) — for now it just dismisses; wire the
    // real scheduling flow in here once that story exists.
    const timeCheckOverlay = document.getElementById( 'aicoach-time-check' );
    const timeCheckYesBtn = document.getElementById( 'aicoach-time-check-yes' );
    const timeCheckNoBtn = document.getElementById( 'aicoach-time-check-no' );
    let timeRemainingPromptShown = false;
    let timeRemainingTimer = null;

    function hideTimeRemainingCheck() {
        timeCheckOverlay?.classList.remove( 'is-visible' );
        timeCheckOverlay?.setAttribute( 'aria-hidden', 'true' );
    }

    function scheduleTimeRemainingCheck( tierMinutes ) {
        const totalMs = TIER_DURATION_MS[ tierMinutes ];
        if ( ! totalMs ) {
            return;
        }
        window.clearTimeout( timeRemainingTimer );
        timeRemainingTimer = window.setTimeout( function () {
            if ( timeRemainingPromptShown ) {
                return;
            }
            timeRemainingPromptShown = true;
            timeCheckOverlay?.classList.add( 'is-visible' );
            timeCheckOverlay?.setAttribute( 'aria-hidden', 'false' );
        }, totalMs * TIME_REMAINING_THRESHOLD_RATIO );
    }

    timeCheckYesBtn?.addEventListener( 'click', function () {
        // Scenario 30 — continue from the current screen with no loss; the
        // overlay was layered on top of it, so there's nothing to resume.
        hideTimeRemainingCheck();
    } );

    timeCheckNoBtn?.addEventListener( 'click', function () {
        hideTimeRemainingCheck();
        // FR-18 isn't built yet — nothing further happens until it exists.
    } );

    // Open a Gary Coach API session (inc/gary-proxy.php) and return its parsed
    // envelope — { session: { id }, say: { text, video: { session_token } }, ... }.
    // Throws on any failure; the caller decides what to do (fall back).
    async function openGarySession( locale ) {
        console.log( '[aicoach] POST', GARY_SESSION_URL, { locale: locale } );
        let res;
        try {
            res = await fetch( GARY_SESSION_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
                body: JSON.stringify( { locale: locale } ),
            } );
        } catch ( networkError ) {
            console.error( '[aicoach] /coach/session network failure (no response reached the browser):', networkError );
            throw networkError;
        }
        console.log( '[aicoach] /coach/session HTTP', res.status, res.statusText );

        // A 502 from Cloudflare/PHP-FPM returns an HTML error page, not JSON —
        // res.json() would throw a generic, unhelpful SyntaxError. Read as text
        // first so a non-JSON response is logged with its real status/body
        // instead of masking it behind "Unexpected token '<'...".
        const rawBody = await res.text();
        let data;
        try {
            data = JSON.parse( rawBody );
        } catch ( parseError ) {
            console.error( '[aicoach] /coach/session returned non-JSON body (HTTP ' + res.status + '):', rawBody.slice( 0, 500 ) );
            throw new Error( 'Gary session request returned a non-JSON response (HTTP ' + res.status + ').' );
        }

        if ( ! res.ok || ! data.session?.id || ! data.say?.video?.session_token ) {
            console.error( '[aicoach] /coach/session did not return a usable session:', { status: res.status, data: data } );
            throw new Error( data.error || 'Failed to open Gary session.' );
        }
        return data;
    }

    // Auto-start on arrival — no tap required (unlike page-portal-poc.php), for a
    // fresh visitor. A returning visitor with saved progress (PO-3102) skips this
    // entirely — see init() below, which decides whether to call start() at all.
    async function start() {
        avatarWrap.dataset.status = 'connecting';
        try {
            const gary = await openGarySession( currentLocale );
            garySessionId = gary.session.id;
            if ( askSamiBtn ) {
                askSamiBtn.disabled = false; // FR-19/PO-3330 — a real session exists now, /message has something to reach
            }

            // disableInputAudio — this page never uses the visitor's microphone
            // (no voice input UI), and omitting it made Anam request mic
            // permission anyway (seen live as a browser permission prompt / a
            // console "Failed to get microphone permission" error). Denying or
            // dismissing that prompt left the stream connected but silent
            // forever, since data-status never reached "live".
            const client = createClient( gary.say.video.session_token, { disableInputAudio: true } );
            activeClient = client;
            let handledClose = false;
            let videoStarted = false; // VIDEO_PLAY_STARTED has been observed firing more than
                                       // once per session (a WebRTC renegotiation, not a real
                                       // second connection) — guard so a repeat event can't
                                       // reset sequenceIndex/the panel out from under an
                                       // already-in-progress runFallback() loop.

            client.addListener( AnamEvent.VIDEO_PLAY_STARTED, async function () {
                if ( videoStarted ) {
                    return;
                }
                videoStarted = true;

                avatarWrap.dataset.status = 'live';
                avatarIsLive = true;

                // Gary's own opening line is real, live content — show it as the
                // intro caption, and give it the same read-or-skip dwell as every
                // other screen before moving on (there's no endOfSpeech event to
                // sync against here — see the top-of-file note on why). Everything
                // after this falls back to the static SCREENS display.
                showPanel( 'intro' );
                const introCaptionEl = getCaptionEl( 'intro' );
                if ( introCaptionEl ) {
                    introCaptionEl.textContent = gary.say.text || SCREENS[ 0 ].script;
                }

                // VIDEO_PLAY_STARTED is the confirmation that the peer connection
                // is actually open — calling client.talk() any earlier (e.g. right
                // after streamToVideoElement() resolves) hits the SDK before the
                // command channel is ready ("sendTalkCommand: peer connection is
                // null"). The session_token alone starts a connected-but-silent
                // stream; talk() is what makes the avatar speak/lip-sync say.text.
                let talkSucceeded = true;
                try {
                    await client.talk( gary.say.text );
                } catch ( talkError ) {
                    talkSucceeded = false;
                    console.warn( '[aicoach] client.talk() failed, avatar stays silent:', talkError );
                }

                if ( talkSucceeded ) {
                    // 25s cap — observed real completion around 9-19s for a
                    // short reply; generous enough to never cut her off, short
                    // enough to never strand a visitor if the event doesn't
                    // arrive. If talk() itself failed there's no speech to
                    // wait for — fall back to the same fixed dwell every
                    // other (non-speaking) screen uses.
                    await waitForSpeechOrSkip( client, 25000 );
                } else {
                    await waitForReadOrSkip();
                }
                sequenceIndex = 1;
                runFallback( true );
            } );
            client.addListener( AnamEvent.CONNECTION_CLOSED, function ( event ) {
                if ( handledClose || sequenceFinished ) {
                    return;
                }
                handledClose = true;
                avatarIsLive = false;
                console.warn( '[aicoach] Sami CONNECTION_CLOSED before sequence finished', event );
                runFallback();
            } );

            await client.streamToVideoElement( AVATAR_VIDEO_ID );
        } catch ( error ) {
            console.warn( '[aicoach] falling back to static intro text:', error );
            // openGarySession() may have succeeded (garySessionId set) even though
            // a later step here failed — best-effort close so that session doesn't
            // stay open on Gary's side for no reason. Token-validation failures
            // never reach this with an id set, since openGarySession() throws
            // before returning one.
            if ( garySessionId ) {
                fetch( garyCloseUrl( garySessionId ), { method: 'POST', headers: { 'X-WP-Nonce': cfg.nonce } } )
                    .catch( function () {} );
                // FR-19/PO-3330 — askSamiBtn may already be enabled at this point
                // (start() flips it right after garySessionId is set, before this
                // await). Without clearing both, Ask Sami would stay clickable
                // against a session Gary just closed on his side.
                garySessionId = null;
                if ( askSamiBtn ) {
                    askSamiBtn.disabled = true;
                }
            }
            runFallback();
        }
    }

    // PO-3102 — resume-on-load helpers. Each applies one piece of saved progress
    // to the same state a normal first-time flow would have set by the point the
    // visitor reaches that stage, so everything downstream (identity/channels
    // submit, final-continue) behaves exactly as if they'd just done it this visit.

    function applyResumedTier( tierMinutes ) {
        selectedTierMinutes = tierMinutes;
        tierConfirmed = true;
        elapsedStartedAt = Date.now();
        scheduleTimeRemainingCheck( tierMinutes );
        SCREENS.push( ...getEquityScreensForTier( tierMinutes ), ...getCompetitionScreensForTier( tierMinutes ) );
        tiers.forEach( function ( tier ) {
            const input = tier.querySelector( '.aicoach-tier-check' );
            if ( input && input.getAttribute( 'data-panel' ) === tierMinutes ) {
                input.checked = true;
            }
        } );
        syncSelected();
    }

    function applyResumedIdentity( identity ) {
        capturedIdentity = identity;
        if ( firstNameInput ) {
            firstNameInput.value = identity.firstName || '';
        }
        if ( lastNameInput ) {
            lastNameInput.value = identity.lastName || '';
        }
        if ( usernameInput ) {
            usernameInput.value = identity.username || '';
        }
        updateIdentityContinueState();
        if ( identityContinueBtn && identityFieldsFilled() ) {
            identityContinueBtn.disabled = true;
            identityContinueBtn.textContent = cfg.i18n?.identitySaved || 'Saved';
        }
    }

    function applyResumedChannels( channels ) {
        capturedChannels = channels;
        channels.forEach( function ( entry ) {
            const { checkbox, field, input } = getChannelParts( entry.channel );
            if ( ! checkbox || ! field || ! input ) {
                return;
            }
            checkbox.checked = true;
            field.hidden = false;
            input.value = entry.value;
        } );
        updateChannelsContinueState();
        if ( channelsContinueBtn && channels.length > 0 ) {
            channelsContinueBtn.disabled = true;
            channelsContinueBtn.textContent = cfg.i18n?.identitySaved || 'Saved';
        }
    }

    // The dynamic SCREENS panel keys in the exact order a fresh flow reaches them
    // for a given tier (or with no tier picked yet) — used to find a saved
    // 'stage's resume index without replaying whatever came before it (Scenario
    // 21). Mirrors exactly what the tier-click handler pushes onto SCREENS.
    function dynamicPanelSequence( tierMinutes ) {
        const tierScreens = tierMinutes
            ? [ ...getEquityScreensForTier( tierMinutes ), ...getCompetitionScreensForTier( tierMinutes ) ].map( function ( s ) { return s.panel; } )
            : [];
        return [ 'intro', 'believe-1', 'believe-2', 'home' ].concat( tierScreens );
    }

    // Panels reached only after the dynamic SCREENS sequence ends — plain
    // showPanel() destinations, not part of SCREENS (see finishSequence()).
    const POST_SEQUENCE_PANELS = [ 'identity', 'comm-channels', 'final-continue' ];

    ( async function init() {
        const progress = await loadProgress();

        // FR-12/13's priority order, already anticipated in detectInitialLocale()'s
        // own comment: saved preference → browser locale → English. currentLocale
        // was already set to the browser/English guess above; selectLocale() (not
        // a bare applyLocale() call) also syncs the language dropdown's own
        // is-current marker — skipping it would leave the dropdown showing the
        // browser-guessed language as "current" while the page's actual text and
        // avatar locale are the resumed one. Its own no-op guard (locale ===
        // currentLocale) only short-circuits when the two already agree, which is
        // harmless — the dropdown already matches in that case.
        if ( progress.language ) {
            selectLocale( progress.language );
        }

        if ( progress.tier ) {
            applyResumedTier( progress.tier );
        }
        if ( progress.identity && ( progress.identity.firstName || progress.identity.lastName || progress.identity.username ) ) {
            applyResumedIdentity( progress.identity );
        }
        if ( progress.channels && progress.channels.length ) {
            applyResumedChannels( progress.channels );
        }

        const stageKey = progress.stage;
        if ( ! stageKey || stageKey === 'intro' ) {
            // Fresh visitor, or never got past the live intro last time — there's
            // no meaningful place to resume "into" (the intro itself is live,
            // real-time content, not something worth resuming mid-sentence), so
            // this is a normal start.
            start();
            return;
        }

        if ( POST_SEQUENCE_PANELS.indexOf( stageKey ) !== -1 ) {
            // Mark the dynamic SCREENS queue as fully consumed so a LATER resume
            // of runFallback() — identity/channels submit's own "loop already
            // exited" pattern, e.g. if the visitor still needs to submit channels
            // from here — only plays newly queued screens (comm-channels/
            // final-continue) instead of replaying the whole sequence from index 0.
            sequenceIndex = SCREENS.length;
            if ( stageKey !== 'identity' ) {
                // Matches what a real identity-form submit already set to reach
                // this far, so finishSequence() (once FINAL_SCREEN finishes)
                // doesn't bounce back to the identity panel.
                sequenceEndDestination = null;
            }
            sequenceFinished = true;
            showPanel( stageKey );
            return;
        }

        const order = dynamicPanelSequence( progress.tier );
        const idx = order.indexOf( stageKey );
        if ( idx === -1 ) {
            // Unrecognized saved stage (e.g. a panel key from an older build) —
            // don't get stuck on it, fall back to a normal fresh start.
            start();
            return;
        }

        // No approved copy exists yet for "this is a continuation" dialogue
        // (Scenario 21) — silent resume for v1: no live Gary session, straight to
        // the exact stage with whatever that panel already shows. runFallback()'s
        // own loop (unchanged) shows + plays sequenceIndex onward, so screens
        // before this one are never replayed.
        sequenceIndex = idx;
        runFallback( false );
    }() );
}
