// resources/js/lib/prizeRules.ts

// How long a winner has to use a gift token. Published on the Incentives page
// and repeated in every Quarter End email that carries a gift card code, so the
// rule a winner is told is the rule the site states. Change it here only.
export const GIFT_TOKEN_REDEEM_RULE =
    'Gift tokens must be redeemed within 12 months of being awarded. After that, an unused prize goes back into the prize fund.'

// How a winner the prize email goes straight to (a parent, or a candidate who
// booked for themselves) claims their gift token. They reply, and only then is
// the card bought, so no money is parked in a code nobody uses (Paul,
// 25 Sep 2026). Prizes that go through a teacher use the "ask their parent to
// email me" wording instead.
export const REPLY_TO_CLAIM =
    "To claim the gift token, just reply to this email and I'll send the gift card straight to you. I'll only use your email address to send the prize."
