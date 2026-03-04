# LearnDash Certificate Generator

A WordPress plugin that automatically generates a certificate and sends a notification email when a user completes a LearnDash course.

---

## What It Does

When a student completes a LearnDash course, this plugin:

1. **Verifies the purchase** — Checks the user's WooCommerce orders to confirm they purchased the course
2. **Prevents duplicates** — Checks if a certificate already exists for this user + course combination
3. **Generates a certificate** — Creates an `urban_certificates` custom post type with a styled HTML certificate containing the student's name, course name, and completion date
4. **Sends an email** — Notifies the student via email that their certificate has been generated

---

## How It Works

The plugin hooks into LearnDash's `learndash_course_completed` action. When fired:

1. Validates the incoming course and user data
2. Queries WooCommerce orders to find a matching purchase
3. Builds a styled HTML certificate with:
   - Student's full name
   - Course name
   - Completion date (localized to WordPress date format)
4. Inserts the certificate as a published `urban_certificates` post
5. Stores `_certificate_user_id` and `_certificate_course_id` as post meta for deduplication
6. Sends an HTML notification email to the student

---

## Requirements

- **WordPress** 5.0+
- **LearnDash LMS** plugin (active)
- **WooCommerce** plugin (active) — courses must be sold as WooCommerce products
- A registered custom post type: `urban_certificates`

---

## Installation

1. Download or clone this repository
2. Place the `learndash_certificates` folder in `wp-content/plugins/` (or drop `ld_certificates.php` into `wp-content/mu-plugins/` for must-use)
3. Activate the plugin from the WordPress admin
4. Ensure the `urban_certificates` custom post type is registered by your theme or another plugin

---

## Configuration

No settings page is needed. The plugin works automatically via the `learndash_course_completed` hook.

### Customizing the Certificate Template

Edit the HTML heredoc inside `generate_ld_certificate()` in `ld_certificates.php`. The following variables are available:

| Variable            | Description                          |
|---------------------|--------------------------------------|
| `$first_name`       | Student's first name (escaped)       |
| `$last_name`        | Student's last name (escaped)        |
| `$safe_course`      | Course name (escaped)                |
| `$completion_date`  | Localized completion date            |

### Customizing the Email

Edit the `$subject` and `$body` variables near the end of the function.

---

## Security

- All user-facing output is escaped with `esc_html()` to prevent XSS
- Post titles are sanitized with `sanitize_text_field()`
- Email addresses are sanitized with `sanitize_email()`
- `kses_remove_filters()` is used narrowly around `wp_insert_post()` and immediately restored
- Incoming hook data is validated with type checks before use

---

## File Structure

```
learndash_certificates/
└── ld_certificates.php    # Main plugin file — all logic in a single function
```

---

## License

MIT
