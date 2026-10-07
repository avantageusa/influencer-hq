/**
 * Screen definitions, tier selection and channel validation for the AI Coach
 * flow (ENGR-7066, PR 2). Moved unchanged from js/aicoach-coach-flow.js so it
 * can be tested without a browser.
 *
 * SCREENS is the live queue the flow appends to (SCREENS.push(...)) as the
 * visitor's choices come in, so it is exported as the same array, not a copy.
 */

export const SCREENS = [
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
export const EQUITY_SCREENS = {
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
export function getEquityScreensForTier( tierMinutes ) {
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
export const COMPETITION_SCREENS = {
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

export function getCompetitionScreensForTier( tierMinutes ) {
    if ( tierMinutes === '2' ) {
        return [];
    }
    return [ COMPETITION_SCREENS.world, COMPETITION_SCREENS.community, COMPETITION_SCREENS.private ];
}

// FR-08 — communication channels. One screen, same for every tier, queued once
// FR-07's identity form succeeds (see the identity submit handler below).
export const COMM_CHANNELS_SCREEN = {
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
export const isValidPhoneNumber = ( v ) => /^\+[1-9]\d{7,14}$/.test( v );

export const CHANNELS = [
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
export const FINAL_SCREEN = {
    panel: 'final-continue',
    script: "Perfect. Thank you. I now know how you'd like us to stay connected. Everything is ready. From this point forward… I'll continue guiding you inside your personal Coaching Center. That's where we'll review your options… answer your questions… and take the next steps together. Remember… you don't have to learn everything today. We'll continue exactly where we leave off. When you're ready… Let's Continue.",
};
