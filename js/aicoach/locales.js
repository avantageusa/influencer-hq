/**
 * Language data and lookups for the AI Coach flow (ENGR-7066, PR 2).
 *
 * Moved unchanged from js/aicoach-coach-flow.js so it can be tested without a
 * browser. The one change is detectLocale(): detectInitialLocale() used to read
 * navigator itself; it now lives in the flow as a one-line wrapper and the
 * matching logic takes the browser's language tags as a parameter.
 */

// FR-13 — manual language selector (Scenario 24/25). Native labels are the ones
// given verbatim in the ticket itself ("Proposed native-language labels, subject to
// localisation review") — not invented here. Locale codes match Gary's
// approved_languages exactly (confirmed via GET /coach/v1/health).
export const SUPPORTED_LOCALES = [
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
export const I18N_EN = {
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
    // PO-3109 — the "Time is up?" screen that replaced FR-17's Yes/No prompt.
    // English only until approved translations exist, like every other new string.
    timeUpTitle: 'Time is up?',
    timeUpKeepTalking: 'Keep Talking Now',
    timeUpSetAppointment: 'Set an Appointment',
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
//     timeUpTitle, timeUpKeepTalking, timeUpSetAppointment (PO-3109), and all 8
//     channel-*-inputLabel /
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
export const I18N_TRANSLATIONS = {
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

export function t( locale, key ) {
    const table = I18N_TRANSLATIONS[ locale ] || {};
    return table[ key ] || I18N_EN[ key ] || '';
}

// FR-12 — language auto-detected from browser locale, with English fallback
// (Scenario 22/23). The ticket's priority order is: saved Luna preference →
// browser locale → English. The saved preference is applied by the flow once
// progress is loaded (PO-3102); this is the browser-locale half.
//
// browserTags: the browser's language tags in preference order (navigator.languages).
export function detectLocale( browserTags ) {
    const supportedCodes = SUPPORTED_LOCALES.map( function ( loc ) { return loc.code; } );

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
