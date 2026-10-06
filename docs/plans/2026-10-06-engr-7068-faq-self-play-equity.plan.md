---
name: Fix the FAQ answer that says influencers earn no equity on their own play
overview: >
  The FAQ answer "Do I have to play the game myself to be an Influencer?" in
  page-portal-more.php says influencers won't earn equity on their own play.
  The live rule (PO-2978 / ENGR-4908) pays 1% on their own real-money play, and
  the FAQ's Equity entry already says so. Change only that clause to match.
todos:
  - id: fix-copy
    content: Replace the outdated clause in the MustPlay FAQ answer
    status: completed
  - id: verify
    content: php -l page-portal-more.php and run tests/*.test.php in php:8.2-cli
    status: completed
---
