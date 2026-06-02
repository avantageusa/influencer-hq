---
name: Add Contact Us page template
overview: >
 Create a dedicated WordPress page template for a new Contact Us page using the provided
 InfluencerHQ copy and style it to match existing dark legal/informational templates in the theme.
 The scope includes template creation, content structure, and responsive styling for readability
 across desktop and mobile. Out of scope: menu wiring, slug creation, and CMS page assignment.
todos:
 - id: review-existing-page-template-patterns
   content: Inspect existing page templates to align markup and visual conventions
   status: completed
 - id: create-contact-us-template-file
   content: Add page-contact-us.php with WordPress template header and page structure
   status: completed
 - id: insert-provided-contact-copy
   content: Add all user-provided Contact Us text and email addresses with mailto links
   status: completed
 - id: style-template-consistently
   content: Add dark responsive styling aligned with existing InfluencerHQ legal pages
   status: completed
 - id: verify
   content: Run tests, lint, and CI gate before merge
   status: blocked
---

# [PO-2783] Contact Us page template

**Ticket:** N/A
**Drafted by:** Cursor (Codex 5.3)

## Problem
The site lacked a dedicated Contact Us page template with clear collaboration, support, and feedback channels. A reusable template was needed so content editors can assign it directly to a WP page.

## Reproduction (bugs only)
Not a bug fix. This is a net-new template addition.

## Approach
- Reviewed existing template style patterns in `page-privacy.php` and `page-terms.php`.
- Added `page-contact-us.php` with `Template Name: Contact Us`.
- Inserted provided copy and grouped contact methods into visual cards.
- Added `mailto:` links for collaboration, support, and suggestions addresses.
- Implemented responsive dark styling consistent with existing InfluencerHQ informational pages.

## Alternatives considered
- Reuse default page template only with editor content — rejected to avoid repeated manual styling.
- Build with shared CSS in stylesheet only — deferred to keep change minimal and self-contained.
- Add via shortcode block — rejected because a page template is easier for content assignment.

## Blast radius
- Adds one new template file only: `page-contact-us.php`.
- No runtime behavior changes to registration, login, or portal flows.
- Visual impact is isolated to pages explicitly assigned this template.

## Notes
- `verify` remains blocked because full test/lint/CI gate was not executed end-to-end.
- IDE lints for the new template file reported no errors.
