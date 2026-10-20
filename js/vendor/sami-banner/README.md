# Sami banner compositor (Gary)

Third-party adapter, kept exactly as delivered so a newer version from Gary is a file swap.
Do not edit these files; change how the theme calls them in `js/aicoach/banner-compositor.js`.

| File | What it is |
| --- | --- |
| `sami-banner-compositor.js` | `createSamiBanner()`: draws the poster on a canvas and composes the avatar's video over it through the affine matrix in the calibration. Makes no network request, creates no session and plays no audio. |
| `calibration.json` | The measured matrix for candidate avatar `cf6b6e7d-be88-4b9b-a31d-5f065b90cd9a` (release `review-only-20261018`; Gary notes target frontend acceptance is still open). |
| `poster.webp` | The 1152x768 approved poster the adapter draws first and returns to on `reset()`. |

The calibration is only valid for that avatar, which is the one Gary's Coach API serves for
`video_profile: "sami-framing-20261018"` (`IHQ_AICOACH_AVATAR_VERSION_PROFILES`, v3). Another
avatar needs a new measurement, a new calibration and a new poster.

Used behind the `ihq_aicoach_banner_compositor` option (`inc/aicoach-banner-compositor.php`).
