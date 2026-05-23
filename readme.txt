# Dynamic Fields Pro — Complete Developer Documentation

Version: 1.0.3 | Requires: WordPress 5.8+, PHP 7.4+

---

## Table of Contents

1. [Installation](#1-installation)
2. [Creating Your First Field Group](#2-creating-your-first-field-group)
3. [All Field Types](#3-all-field-types)
4. [Location Rules](#4-location-rules)
5. [Template Tag API](#5-template-tag-api)
6. [Landing Page Integration](#6-landing-page-integration)
7. [Dynamic FAQ](#7-dynamic-faq)
8. [Repeater Field — Team, Testimonials, Pricing](#8-repeater-field)
9. [Gallery Field](#9-gallery-field)
10. [File Field](#10-file-field)
11. [WYSIWYG Editor Field](#11-wysiwyg-editor-field)
12. [REST API](#12-rest-api)
13. [Registering Custom Field Types](#13-registering-custom-field-types)
14. [Hooks & Filters Reference](#14-hooks--filters-reference)
15. [Common Patterns & Recipes](#15-common-patterns--recipes)

---

## 1. Installation

### Method A — Upload via wp-admin
1. Go to **Plugins → Add New → Upload Plugin**
2. Choose `dynamic-fields-pro.zip`
3. Click **Install Now → Activate**

### Method B — FTP / cPanel
Upload the `dynamic-fields-pro/` folder to `/wp-content/plugins/`, then activate from **Plugins** screen.

After activation you will see **Dynamic Fields** in the WordPress admin sidebar.

---

## 2. Creating Your First Field Group

A **Field Group** is a collection of custom fields assigned to one or more post types / pages.

### Steps

1. Go to **Dynamic Fields → Add New**
2. Enter a **Group Title** (e.g. "Landing Page Fields")
3. Click **+ Add Field** to add fields
4. Set each field's **Label**, **Name** (slug), and **Type**
5. Click the **Location Rules** tab and set where the group appears (e.g. Post Type = Page)
6. Click **Save Field Group**

The fields will now appear as a meta box on the edit screen of the assigned posts/pages.

---

## 3. All Field Types

| Type | Returns | Use for |
|------|---------|---------|
| Text | `string` | Short text, headings, labels |
| Textarea | `string` | Multi-line plain text |
| Number | `int/float` | Prices, counts, ratings |
| Email | `string` | Email addresses |
| URL | `string` | Links, button URLs |
| Password | `string` | Stored hashed |
| Select | `string` or `array` | Dropdown choice |
| Checkbox | `array` | Multiple choices |
| Radio | `string` | Single choice |
| True / False | `1` or `0` | Toggle switches |
| Post Object | `WP_Post` | Related post |
| Relationship | `WP_Post[]` | Multiple related posts |
| Taxonomy | `WP_Term[]` | Categories / tags |
| User | `WP_User` | Author, team member |
| Date Picker | `string` | Dates (Ymd format) |
| Color Picker | `string` | Hex color value |
| Image | `array` | Single image |
| Gallery | `array[]` | Multiple images |
| File | `array` | Any file upload |
| WYSIWYG | `string` | Rich text / HTML |
| Repeater | `array[]` | Repeating row sets |

---

## 4. Location Rules

Location rules control which posts/pages show your field group.

### Available Rule Types

| Rule | Example Values |
|------|---------------|
| Post Type | `post`, `page`, `product` |
| Page Template | `templates/landing.php` |
| Post | Specific post by title |
| Taxonomy Term | Category, tag, custom term |
| User Role | `administrator`, `editor` |
| Page Parent | Parent page ID |
| Post Format | `video`, `gallery`, `aside` |
| Post Status | `publish`, `draft` |
| Current User | Logged in user |
| Current User Role | `subscriber` |
| Attachment MIME | `image/png`, `application/pdf` |

### Multiple Rules

- Rules **within a group** = AND (all must match)
- **Multiple groups** = OR (any group can match)

**Example:** Show on "Page" AND template "landing.php"
```
Group 1:  Post Type == page  AND  Page Template == templates/landing.php
```

**Example:** Show on "Page" OR "Product"
```
Group 1:  Post Type == page
Group 2:  Post Type == product
```

---

## 5. Template Tag API

All functions work in theme templates (`single.php`, `page.php`, `front-page.php`, etc.).

### `get_field( $name, $post_id = null )`

Returns the field value. Uses current post if `$post_id` is omitted.

```php
$title = get_field( 'hero_title' );
$title = get_field( 'hero_title', 42 );       // specific post
$title = get_field( 'hero_title', 'user_1' ); // user meta (future)
```

### `the_field( $name, $post_id = null )`

Echoes the field value (escaped with `wp_kses_post`).

```php
<h1><?php the_field( 'hero_title' ); ?></h1>
```

### `get_fields( $post_id = null )`

Returns all field values for a post as an associative array.

```php
$fields = get_fields();
if ( $fields ) {
    foreach ( $fields as $name => $value ) {
        echo $name . ': ' . $value . '<br>';
    }
}
```

### `update_field( $name, $value, $post_id = null )`

Programmatically save a field value.

```php
update_field( 'hero_title', 'Welcome to Our Site', $post_id );
update_field( 'show_cta', 1, $post_id ); // true/false
```

### `delete_field( $name, $post_id = null )`

Delete a field value.

```php
delete_field( 'old_banner', $post_id );
```

### Repeater loop functions

```php
have_rows( $name, $post_id = null )  // bool — advances internal pointer
the_row()                            // sets current row
get_sub_field( $name )               // returns sub-field value in current row
the_sub_field( $name )               // echoes sub-field value
get_row()                            // full current row as array
get_row_index()                      // 0-based current row index
reset_rows( $name, $post_id = null ) // reset loop pointer
```

---

## 6. Landing Page Integration

### Step 1 — Create the Field Group

Go to **Dynamic Fields → Add New** and create a group called **"Landing Page"** with these fields:

| Field Label | Field Name | Type |
|-------------|-----------|------|
| Hero Title | `hero_title` | Text |
| Hero Subtitle | `hero_subtitle` | Textarea |
| Hero Button Text | `hero_btn_text` | Text |
| Hero Button URL | `hero_btn_url` | URL |
| Hero Background Image | `hero_bg_image` | Image |
| Show CTA Section | `show_cta` | True / False |
| CTA Heading | `cta_heading` | Text |
| CTA Button Text | `cta_btn_text` | Text |
| CTA Button URL | `cta_btn_url` | URL |
| Features | `features` | Repeater |
| &nbsp;&nbsp;→ Icon (sub-field) | `icon` | Image |
| &nbsp;&nbsp;→ Title (sub-field) | `title` | Text |
| &nbsp;&nbsp;→ Description (sub-field) | `description` | Textarea |

Set **Location** to: Post Type == page, Page Template == your landing page template.

---

### Step 2 — Landing Page Template

Create `templates/landing-page.php` in your theme:

```php
<?php
/**
 * Template Name: Landing Page
 */
get_header();
?>

<!-- ═══════════════════════════ HERO ═══════════════════════════ -->
<?php
$hero_title    = get_field( 'hero_title' );
$hero_subtitle = get_field( 'hero_subtitle' );
$hero_btn_text = get_field( 'hero_btn_text' );
$hero_btn_url  = get_field( 'hero_btn_url' );
$hero_bg       = get_field( 'hero_bg_image' ); // returns image array
?>

<section class="hero" <?php if ( $hero_bg ) : ?>
    style="background-image: url('<?php echo esc_url( $hero_bg['url'] ); ?>')"
<?php endif; ?>>
    <div class="hero-inner">
        <?php if ( $hero_title ) : ?>
            <h1 class="hero-title"><?php echo esc_html( $hero_title ); ?></h1>
        <?php endif; ?>

        <?php if ( $hero_subtitle ) : ?>
            <p class="hero-subtitle"><?php echo esc_html( $hero_subtitle ); ?></p>
        <?php endif; ?>

        <?php if ( $hero_btn_text && $hero_btn_url ) : ?>
            <a href="<?php echo esc_url( $hero_btn_url ); ?>" class="btn btn-primary">
                <?php echo esc_html( $hero_btn_text ); ?>
            </a>
        <?php endif; ?>
    </div>
</section>

<!-- ═══════════════════════════ FEATURES ════════════════════════ -->
<?php if ( have_rows( 'features' ) ) : ?>
<section class="features">
    <div class="features-grid">
        <?php while ( have_rows( 'features' ) ) : the_row(); ?>
        <?php
            $icon  = get_sub_field( 'icon' );
            $title = get_sub_field( 'title' );
            $desc  = get_sub_field( 'description' );
        ?>
        <div class="feature-card">
            <?php if ( $icon ) : ?>
                <img src="<?php echo esc_url( $icon['url'] ); ?>"
                     alt="<?php echo esc_attr( $icon['alt'] ); ?>"
                     class="feature-icon">
            <?php endif; ?>
            <h3><?php echo esc_html( $title ); ?></h3>
            <p><?php echo esc_html( $desc ); ?></p>
        </div>
        <?php endwhile; ?>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════ CTA ═════════════════════════════ -->
<?php if ( get_field( 'show_cta' ) ) : ?>
<section class="cta">
    <h2><?php the_field( 'cta_heading' ); ?></h2>
    <a href="<?php the_field( 'cta_btn_url' ); ?>" class="btn btn-cta">
        <?php the_field( 'cta_btn_text' ); ?>
    </a>
</section>
<?php endif; ?>

<?php get_footer(); ?>
```

---

## 7. Dynamic FAQ

FAQs are a perfect use case for the **Repeater** field.

### Field Setup

Create a field group called **"FAQ Section"** with:

| Field Label | Field Name | Type |
|-------------|-----------|------|
| FAQ Items | `faq_items` | Repeater |
| &nbsp;&nbsp;→ Question | `question` | Text |
| &nbsp;&nbsp;→ Answer | `answer` | WYSIWYG |

Set sub-fields inside the Repeater field settings.

### Template Code

```php
<?php if ( have_rows( 'faq_items' ) ) : ?>
<section class="faq">
    <h2>Frequently Asked Questions</h2>
    <div class="faq-list">
        <?php while ( have_rows( 'faq_items' ) ) : the_row(); ?>
        <div class="faq-item">
            <button class="faq-question" aria-expanded="false">
                <?php echo esc_html( get_sub_field( 'question' ) ); ?>
                <span class="faq-icon">+</span>
            </button>
            <div class="faq-answer" hidden>
                <?php echo wp_kses_post( get_sub_field( 'answer' ) ); ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</section>

<style>
.faq-item { border-bottom: 1px solid #eee; padding: 4px 0; }
.faq-question { width: 100%; text-align: left; background: none; border: none;
    font-size: 16px; font-weight: 600; padding: 16px 0; cursor: pointer;
    display: flex; justify-content: space-between; }
.faq-answer { padding: 0 0 16px; color: #555; }
</style>

<script>
document.querySelectorAll('.faq-question').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var answer  = this.nextElementSibling;
        var icon    = this.querySelector('.faq-icon');
        var open    = this.getAttribute('aria-expanded') === 'true';
        this.setAttribute('aria-expanded', !open);
        answer.hidden = open;
        icon.textContent = open ? '+' : '−';
    });
});
</script>
<?php endif; ?>
```

---

## 8. Repeater Field

### Team Members

**Fields:**

| Label | Name | Type |
|-------|------|------|
| Team Members | `team_members` | Repeater |
| → Photo | `photo` | Image |
| → Name | `name` | Text |
| → Role | `role` | Text |
| → Bio | `bio` | Textarea |
| → LinkedIn URL | `linkedin` | URL |

**Template:**

```php
<?php if ( have_rows( 'team_members' ) ) : ?>
<section class="team">
    <div class="team-grid">
        <?php while ( have_rows( 'team_members' ) ) : the_row();
            $photo   = get_sub_field( 'photo' );
            $name    = get_sub_field( 'name' );
            $role    = get_sub_field( 'role' );
            $bio     = get_sub_field( 'bio' );
            $linkedin= get_sub_field( 'linkedin' );
        ?>
        <div class="team-card">
            <?php if ( $photo ) : ?>
                <img src="<?php echo esc_url( $photo['sizes']['medium'] ); ?>"
                     alt="<?php echo esc_attr( $photo['alt'] ); ?>">
            <?php endif; ?>
            <h3><?php echo esc_html( $name ); ?></h3>
            <p class="role"><?php echo esc_html( $role ); ?></p>
            <p><?php echo esc_html( $bio ); ?></p>
            <?php if ( $linkedin ) : ?>
                <a href="<?php echo esc_url( $linkedin ); ?>" target="_blank">LinkedIn</a>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
</section>
<?php endif; ?>
```

---

### Pricing Plans

**Fields:**

| Label | Name | Type |
|-------|------|------|
| Pricing Plans | `pricing_plans` | Repeater |
| → Plan Name | `plan_name` | Text |
| → Price | `price` | Text |
| → Period | `period` | Select (Monthly/Yearly) |
| → Features | `features_list` | Textarea |
| → Button Text | `btn_text` | Text |
| → Button URL | `btn_url` | URL |
| → Highlight | `is_popular` | True / False |

**Template:**

```php
<?php if ( have_rows( 'pricing_plans' ) ) : ?>
<section class="pricing">
    <div class="pricing-grid">
        <?php while ( have_rows( 'pricing_plans' ) ) : the_row();
            $popular  = get_sub_field( 'is_popular' );
            $features = array_filter( array_map( 'trim',
                            explode( "\n", get_sub_field( 'features_list' ) ) ) );
        ?>
        <div class="pricing-card<?php echo $popular ? ' popular' : ''; ?>">
            <?php if ( $popular ) : ?><span class="badge">Most Popular</span><?php endif; ?>
            <h3><?php echo esc_html( get_sub_field( 'plan_name' ) ); ?></h3>
            <div class="price">
                <?php echo esc_html( get_sub_field( 'price' ) ); ?>
                <span><?php echo esc_html( get_sub_field( 'period' ) ); ?></span>
            </div>
            <ul>
                <?php foreach ( $features as $feature ) : ?>
                    <li><?php echo esc_html( $feature ); ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="<?php echo esc_url( get_sub_field( 'btn_url' ) ); ?>"
               class="btn"><?php echo esc_html( get_sub_field( 'btn_text' ) ); ?></a>
        </div>
        <?php endwhile; ?>
    </div>
</section>
<?php endif; ?>
```

---

### Testimonials

**Fields:**

| Label | Name | Type |
|-------|------|------|
| Testimonials | `testimonials` | Repeater |
| → Quote | `quote` | Textarea |
| → Author Name | `author_name` | Text |
| → Author Title | `author_title` | Text |
| → Author Photo | `author_photo` | Image |
| → Rating | `rating` | Select (1–5) |

**Template:**

```php
<?php if ( have_rows( 'testimonials' ) ) : ?>
<section class="testimonials">
    <div class="testimonials-slider">
        <?php while ( have_rows( 'testimonials' ) ) : the_row();
            $photo  = get_sub_field( 'author_photo' );
            $rating = (int) get_sub_field( 'rating' );
        ?>
        <div class="testimonial">
            <div class="stars"><?php echo str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ); ?></div>
            <blockquote><?php echo esc_html( get_sub_field( 'quote' ) ); ?></blockquote>
            <div class="author">
                <?php if ( $photo ) : ?>
                    <img src="<?php echo esc_url( $photo['sizes']['thumbnail'] ); ?>"
                         alt="<?php echo esc_attr( $photo['alt'] ); ?>"
                         class="author-avatar">
                <?php endif; ?>
                <div>
                    <strong><?php echo esc_html( get_sub_field( 'author_name' ) ); ?></strong>
                    <span><?php echo esc_html( get_sub_field( 'author_title' ) ); ?></span>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</section>
<?php endif; ?>
```

---

### Nested Repeaters (Sections with Items)

```php
<?php while ( have_rows( 'page_sections' ) ) : the_row(); ?>
    <section>
        <h2><?php echo esc_html( get_sub_field( 'section_title' ) ); ?></h2>
        <?php if ( have_rows( 'section_items' ) ) : ?>
            <ul>
                <?php while ( have_rows( 'section_items' ) ) : the_row(); ?>
                    <li><?php echo esc_html( get_sub_field( 'item_text' ) ); ?></li>
                <?php endwhile; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php endwhile; ?>
```

---

## 9. Gallery Field

Returns an array of image arrays by default.

### Basic Gallery

```php
<?php
$images = get_field( 'project_gallery' );
if ( $images ) : ?>
<div class="gallery">
    <?php foreach ( $images as $image ) : ?>
    <a href="<?php echo esc_url( $image['url'] ); ?>">
        <img src="<?php echo esc_url( $image['sizes']['medium'] ); ?>"
             alt="<?php echo esc_attr( $image['alt'] ); ?>"
             width="<?php echo absint( $image['sizes']['medium-width'] ); ?>"
             height="<?php echo absint( $image['sizes']['medium-height'] ); ?>">
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
```

### Gallery with Lightbox (URL return format)

In field settings set **Return Format = URL**, then:

```php
$urls = get_field( 'project_gallery' ); // plain array of URLs
foreach ( $urls as $url ) {
    echo '<img src="' . esc_url( $url ) . '" loading="lazy">';
}
```

---

## 10. File Field

### Display a Downloadable File

```php
<?php
$file = get_field( 'brochure_pdf' ); // returns array by default
if ( $file ) : ?>
<a href="<?php echo esc_url( $file['url'] ); ?>"
   class="btn"
   download="<?php echo esc_attr( $file['filename'] ); ?>">
    Download Brochure
    <?php if ( $file['filesize'] ) : ?>
        <small>(<?php echo esc_html( size_format( $file['filesize'] ) ); ?>)</small>
    <?php endif; ?>
</a>
<?php endif; ?>
```

### File Array Keys

```php
$file = get_field( 'attachment' );
$file['id']        // attachment post ID
$file['url']       // full URL to file
$file['title']     // attachment title
$file['filename']  // e.g. brochure.pdf
$file['filesize']  // bytes
$file['mime_type'] // e.g. application/pdf
```

---

## 11. WYSIWYG Editor Field

Returns HTML string. Always use `wp_kses_post()` or `echo` directly — **never** escape with `esc_html()` as that would break formatting.

```php
<?php
$content = get_field( 'page_content' );
if ( $content ) {
    echo wp_kses_post( $content );
}
?>
```

Or use `the_field()` which already applies `wp_kses_post()`:

```php
<?php the_field( 'page_content' ); ?>
```

---

## 12. REST API

### GET — Read field values

```
GET /wp-json/dfp/v1/fields/{post_id}
```

**Response:**
```json
{
    "post_id": 42,
    "post_type": "page",
    "fields": {
        "hero_title": "Welcome",
        "hero_bg_image": {
            "id": 7,
            "url": "https://example.com/wp-content/uploads/hero.jpg",
            "alt": "Hero background"
        },
        "features": [
            { "title": "Fast", "description": "Really fast" },
            { "title": "Secure", "description": "Rock solid" }
        ]
    }
}
```

Authentication: public posts are readable without auth. Private posts require a logged-in user.

---

### POST — Update field values

```
POST /wp-json/dfp/v1/fields/{post_id}
Content-Type: application/json
Authorization: Basic <base64 user:app-password>
```

**Request body:**
```json
{
    "hero_title": "New Headline",
    "show_cta": 1,
    "hero_bg_image": 7
}
```

**Response:**
```json
{
    "success": true,
    "post_id": 42,
    "updated_fields": ["hero_title", "show_cta", "hero_bg_image"]
}
```

Requires `edit_post` capability.

---

### JavaScript / Fetch Example

```js
// Read fields
fetch('/wp-json/dfp/v1/fields/42')
    .then(res => res.json())
    .then(data => {
        console.log(data.fields.hero_title);
    });

// Update fields (needs authentication)
fetch('/wp-json/dfp/v1/fields/42', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({ hero_title: 'Updated Title' })
})
.then(res => res.json())
.then(console.log);
```

---

## 13. Registering Custom Field Types

You can add your own field types via the `dfp/register_fields` action.

### Minimal Example

```php
add_action( 'dfp/register_fields', function( $dfp_fields ) {

    class My_Star_Rating_Field extends DFP_Field_Base {

        public function get_type()  { return 'star_rating'; }
        public function get_label() { return 'Star Rating'; }

        public function get_defaults() {
            return [ 'min' => 1, 'max' => 5 ];
        }

        public function render_field( $field, $value ) {
            $max = isset( $field['max'] ) ? absint( $field['max'] ) : 5;
            $val = absint( $value );
            echo '<div class="star-rating-field">';
            for ( $i = 1; $i <= $max; $i++ ) {
                echo '<label>';
                echo '<input type="radio" name="' . esc_attr( $field['key'] ) . '" value="' . $i . '"'
                   . checked( $val, $i, false ) . '>';
                echo '<span class="star">' . ( $i <= $val ? '★' : '☆' ) . '</span>';
                echo '</label>';
            }
            echo '</div>';
        }

        public function render_field_settings( $field ) {
            $this->row(
                'Max Stars',
                '<input type="number" min="1" max="10" name="' . $field['key'] . '[max]"
                    value="' . esc_attr( isset( $field['max'] ) ? $field['max'] : 5 ) . '">'
            );
        }

        public function update_value( $value, $post_id, $field ) {
            return update_post_meta( $post_id, $field['key'], absint( $value ) );
        }

        public function load_value( $value, $post_id, $field ) {
            return absint( $value );
        }

        public function validate_value( $valid, $value, $field ) {
            return parent::validate_value( $valid, $value, $field );
        }
    }

    $dfp_fields->register_field_type( new My_Star_Rating_Field() );
} );
```

Place this code in your theme's `functions.php` or a custom plugin file.

---

## 14. Hooks & Filters Reference

### `dfp/register_fields` — action

Register custom field types.

```php
add_action( 'dfp/register_fields', function( $dfp_fields ) {
    $dfp_fields->register_field_type( new My_Custom_Field() );
} );
```

---

### `dfp/location/rule_params` — filter

Add custom location rule parameters to the builder.

```php
add_filter( 'dfp/location/rule_params', function( $params ) {
    $params['my_custom_rule'] = 'My Custom Rule';
    return $params;
} );
```

---

### `dfp/location/rule_values` — filter

Populate the value dropdown for a custom rule param.

```php
add_filter( 'dfp/location/rule_values', function( $values, $param ) {
    if ( $param === 'my_custom_rule' ) {
        $values = [
            'value_a' => 'Option A',
            'value_b' => 'Option B',
        ];
    }
    return $values;
}, 10, 2 );
```

---

## 15. Common Patterns & Recipes

### Check if a field has a value before rendering

```php
<?php $value = get_field( 'promo_banner' ); ?>
<?php if ( $value ) : ?>
    <div class="banner"><?php echo esc_html( $value ); ?></div>
<?php endif; ?>
```

---

### Output a color picker as inline CSS

```php
<?php $color = get_field( 'brand_color' ); ?>
<?php if ( $color ) : ?>
<style>
    :root { --brand-color: <?php echo esc_attr( $color ); ?>; }
    .btn-primary { background: var(--brand-color); }
</style>
<?php endif; ?>
```

---

### Conditional section visibility (True / False field)

```php
<?php if ( get_field( 'show_video_section' ) ) : ?>
    <section class="video">
        <!-- video content -->
    </section>
<?php endif; ?>
```

---

### Date picker — format output

Fields are stored in `Ymd` format (e.g. `20260101`).

```php
<?php
$raw  = get_field( 'event_date' ); // "20260101"
$date = $raw ? DateTime::createFromFormat( 'Ymd', $raw ) : null;
?>
<?php if ( $date ) : ?>
    <time datetime="<?php echo $date->format( 'Y-m-d' ); ?>">
        <?php echo $date->format( 'F j, Y' ); // January 1, 2026 ?>
    </time>
<?php endif; ?>
```

---

### Post Object — link to related post

```php
<?php $related = get_field( 'related_post' ); // WP_Post object ?>
<?php if ( $related ) : ?>
    <a href="<?php echo esc_url( get_permalink( $related ) ); ?>">
        <?php echo esc_html( $related->post_title ); ?>
    </a>
<?php endif; ?>
```

---

### Relationship field — list of related posts

```php
<?php $posts = get_field( 'related_articles' ); // array of WP_Post ?>
<?php if ( $posts ) : ?>
<ul class="related">
    <?php foreach ( $posts as $p ) : ?>
        <li>
            <a href="<?php echo esc_url( get_permalink( $p ) ); ?>">
                <?php echo esc_html( $p->post_title ); ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>
```

---

### Taxonomy field — linked terms

```php
<?php $terms = get_field( 'featured_tags' ); // array of WP_Term ?>
<?php if ( $terms ) : ?>
<div class="tags">
    <?php foreach ( $terms as $term ) : ?>
        <a href="<?php echo esc_url( get_term_link( $term ) ); ?>"
           class="tag">
            <?php echo esc_html( $term->name ); ?>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
```

---

### Select field with array return format

```php
<?php $status = get_field( 'project_status' ); ?>
<!-- With return format = "Both" -->
<span class="badge badge-<?php echo esc_attr( $status['value'] ); ?>">
    <?php echo esc_html( $status['label'] ); ?>
</span>
```

---

### Get fields for a specific post (not current)

```php
<?php
$hero = get_field( 'hero_title', 5 );   // post ID 5
$logo = get_field( 'company_logo', 10 ); // post ID 10

// Useful for a front-page that pulls data from a settings page:
$settings_page_id = get_option( 'dfp_settings_page_id' );
$phone = get_field( 'contact_phone', $settings_page_id );
?>
```

---

### Reset and re-loop a repeater

```php
<?php
// First loop — count rows.
$count = 0;
if ( have_rows( 'team_members' ) ) {
    while ( have_rows( 'team_members' ) ) { the_row(); $count++; }
}

// Reset so you can loop again.
reset_rows( 'team_members' );

// Second loop — render.
if ( have_rows( 'team_members' ) ) {
    echo '<p>Total members: ' . $count . '</p>';
    while ( have_rows( 'team_members' ) ) {
        the_row();
        echo '<p>' . esc_html( get_sub_field( 'name' ) ) . '</p>';
    }
}
?>
```

---

### Import / Export Field Groups

- **Export:** Dynamic Fields list → click **Export** next to a group → downloads JSON
- **Import:** Dynamic Fields list → scroll to **Import Field Group (JSON)** panel → paste JSON → Import

Field keys are regenerated on import to prevent conflicts.

---

### ACF Compatibility

All global template functions (`get_field`, `have_rows`, etc.) are wrapped in `function_exists()` checks. If ACF is also active, ACF's functions take precedence automatically. No conflicts.

---

## Quick Reference Card

```
get_field( 'name' )                 → value
the_field( 'name' )                 → echoes value
get_fields()                        → all fields as array
update_field( 'name', $val )        → save value
delete_field( 'name' )              → remove value

have_rows( 'repeater_name' )        → bool (advances pointer)
the_row()                           → sets current row
get_sub_field( 'sub_name' )         → sub-field value
the_sub_field( 'sub_name' )         → echoes sub-field value
get_row()                           → full row array
get_row_index()                     → 0-based index
reset_rows( 'repeater_name' )       → reset pointer

REST GET  /wp-json/dfp/v1/fields/{post_id}
REST POST /wp-json/dfp/v1/fields/{post_id}
```

---

*Dynamic Fields Pro v1.0.3 — Built as a complete ACF alternative for WordPress.*


=== Dynamic Fields Pro ===
Contributors: dynamicfieldspro
Tags: custom fields, meta box, field groups, ACF alternative, repeater, gallery, wysiwyg, faq, woocommerce
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.0.3
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A complete custom fields solution for WordPress. Create field groups, assign them to posts/pages/CPTs with flexible location rules, and access field data with a clean template-tag API.

== Description ==

Dynamic Fields Pro is a full-featured custom fields plugin for WordPress. It provides:

* **22 field types**: Text, Textarea, Number, Email, URL, Password, Select, Checkbox, Radio, True/False, Post Object, Relationship, Taxonomy, User, Date Picker, Color Picker, Image, Gallery, File, WYSIWYG Editor, Repeater
* **Visual field builder** with drag-to-reorder
* **Location rules** — show field groups based on post type, template, taxonomy, user role, page parent, and more
* **Repeater fields** with collapse/expand, drag-to-sort rows, nested repeaters
* **Gallery field** — multi-image picker with thumbnail grid and min/max limits
* **File field** — any file type upload with size display and allowed mime types filter
* **WYSIWYG Editor field** — full TinyMCE editor per field
* **REST API** endpoints: `GET /wp-json/dfp/v1/fields/{post_id}` and `POST /wp-json/dfp/v1/fields/{post_id}`
* **Import / Export** field groups as JSON
* **Template tag API** compatible with ACF patterns (with `function_exists()` guards)

== Installation ==

1. Upload the `dynamic-fields-pro` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** screen in WordPress Admin
3. Navigate to **Dynamic Fields** in the admin menu
4. Click **Add New** to create your first field group

== Creating Your First Field Group ==

1. Go to **Dynamic Fields → Add New**
2. Enter a **Group Title** (e.g. "Landing Page Fields")
3. Click **+ Add Field** to add fields
4. Set each field's Label, Name, and Type
5. Click the **Location Rules** tab — set where the group appears (e.g. Post Type = Page)
6. Click **Save Field Group**

The fields will now appear as a meta box on the edit screen of the assigned posts/pages.

== All Field Types ==

= Basic =
* **Text** — Short strings, headings, labels
* **Textarea** — Multi-line plain text
* **Number** — Prices, counts, ratings
* **Email** — Email addresses
* **URL** — Links, button URLs
* **Password** — Stored hashed

= Content =
* **Image** — Single image from media library (returns array with url, alt, width, height, sizes)
* **Gallery** — Multiple images, thumbnail grid, min/max limits, return format: array/url/id
* **File** — Any file upload, allowed mime types filter, return format: array/url/id
* **WYSIWYG Editor** — Full TinyMCE editor with toolbar and media upload options

= Choice =
* **Select** — Dropdown, return as value or array {value, label}
* **Checkbox** — Multiple choices, returns array
* **Radio** — Single choice
* **True / False** — Toggle switch, returns 1 or 0

= Relational =
* **Post Object** — Returns WP_Post object
* **Relationship** — Multiple posts, returns WP_Post[]
* **Taxonomy** — Categories/tags, returns WP_Term[]
* **User** — Returns WP_User object

= jQuery =
* **Date Picker** — Stored as Ymd (20260101), configurable display format
* **Color Picker** — Returns hex value (#ffffff)

= Layout =
* **Repeater** — Repeating row sets with sub-fields, supports nested repeaters

== Template Tag API ==

= get_field() =

Retrieve the value of a custom field.

    $value = get_field( 'hero_title' );
    $value = get_field( 'hero_title', $post_id ); // specific post

= the_field() =

Echo the value (escaped with wp_kses_post).

    the_field( 'hero_title' );

= get_fields() =

All fields for a post as an associative array.

    $fields = get_fields();
    foreach ( $fields as $name => $value ) {
        echo $name . ': ' . $value;
    }

= update_field() =

Programmatically update a field value.

    update_field( 'hero_title', 'New Value', $post_id );

= delete_field() =

Delete a field value.

    delete_field( 'hero_title', $post_id );

= Repeater fields =

    if ( have_rows( 'team_members' ) ) {
        while ( have_rows( 'team_members' ) ) {
            the_row();
            $name  = get_sub_field( 'name' );
            $role  = get_sub_field( 'role' );
            $photo = get_sub_field( 'photo' ); // returns image array
            echo '<p>' . esc_html( $name ) . ' — ' . esc_html( $role ) . '</p>';
        }
    }

= Nested repeaters =

    while ( have_rows( 'sections' ) ) {
        the_row();
        echo '<h2>' . esc_html( get_sub_field( 'section_title' ) ) . '</h2>';
        while ( have_rows( 'items' ) ) {
            the_row();
            echo '<p>' . esc_html( get_sub_field( 'item_text' ) ) . '</p>';
        }
    }

= get_row() and get_row_index() =

    while ( have_rows( 'slides' ) ) {
        the_row();
        $index = get_row_index(); // 0-based
        $row   = get_row();       // full row as associative array
        echo 'Slide ' . ( $index + 1 ) . ': ' . $row['slide_title'];
    }

= reset_rows() =

Reset the loop pointer so you can iterate again.

    reset_rows( 'team_members', $post_id );

= Image field =

    $image = get_field( 'hero_image' );
    if ( $image ) {
        echo '<img src="' . esc_url( $image['url'] ) . '"
                   width="' . absint( $image['width'] ) . '"
                   height="' . absint( $image['height'] ) . '"
                   alt="' . esc_attr( $image['alt'] ) . '">';
    }

= Gallery field =

    $images = get_field( 'project_gallery' ); // array of image arrays
    if ( $images ) {
        foreach ( $images as $image ) {
            echo '<img src="' . esc_url( $image['sizes']['medium'] ) . '"
                       alt="' . esc_attr( $image['alt'] ) . '">';
        }
    }

= File field =

    $file = get_field( 'brochure_pdf' );
    if ( $file ) {
        echo '<a href="' . esc_url( $file['url'] ) . '" download>'
           . esc_html( $file['filename'] ) . '</a>';
        // $file keys: id, url, title, filename, filesize, mime_type
    }

= WYSIWYG field =

    $content = get_field( 'page_content' );
    if ( $content ) {
        echo wp_kses_post( $content );
    }
    // or simply:
    the_field( 'page_content' );

= Post Object field =

    $related_post = get_field( 'related_post' ); // WP_Post object
    if ( $related_post ) {
        echo '<a href="' . esc_url( get_permalink( $related_post ) ) . '">'
           . esc_html( $related_post->post_title ) . '</a>';
    }

= Taxonomy field =

    $terms = get_field( 'featured_categories' ); // array of WP_Term objects
    if ( $terms ) {
        foreach ( $terms as $term ) {
            echo '<a href="' . esc_url( get_term_link( $term ) ) . '">'
               . esc_html( $term->name ) . '</a>';
        }
    }

= User field =

    $author = get_field( 'article_author' ); // WP_User object
    if ( $author ) {
        echo esc_html( $author->display_name );
    }

= True / False field =

    if ( get_field( 'show_sidebar' ) ) {
        get_sidebar();
    }

= Select / Radio field with Return Format = array =

    $color = get_field( 'brand_color' ); // { value, label }
    echo $color['label']; // "Midnight Blue"
    echo $color['value']; // "midnight_blue"

= Date Picker field =

    $raw  = get_field( 'event_date' ); // "20260101"
    $date = $raw ? DateTime::createFromFormat( 'Ymd', $raw ) : null;
    if ( $date ) {
        echo $date->format( 'F j, Y' ); // January 1, 2026
    }

= Color Picker as CSS variable =

    $color = get_field( 'brand_color' );
    if ( $color ) {
        echo '<style>:root { --brand-color: ' . esc_attr( $color ) . '; }</style>';
    }

== Location Rules ==

Location rules control which posts/pages show a field group.

Available rule types:
* Post Type — page, post, product, any CPT
* Page Template — e.g. templates/landing.php
* Post — specific post by title
* Taxonomy Term — any registered taxonomy term
* User Role — administrator, editor, author, etc.
* Page Parent — specific parent page ID
* Post Format — video, gallery, aside, etc.
* Post Status — publish, draft, pending, etc.
* Current User — logged in user
* Current User Role — subscriber, contributor, etc.
* Attachment MIME Type — image/png, application/pdf, etc.

Rules within a group = AND (all must match).
Multiple groups = OR (any group can match).

Example — show on Page AND landing template:

    Group 1:  Post Type == page  AND  Page Template == templates/landing.php

Example — show on Page OR Product:

    Group 1:  Post Type == page
    Group 2:  Post Type == product

== Landing Page Integration ==

Step 1: Create a field group called "Landing Page" with these fields:

    hero_title       → Text
    hero_subtitle    → Textarea
    hero_btn_text    → Text
    hero_btn_url     → URL
    hero_bg_image    → Image
    show_cta         → True / False
    cta_heading      → Text
    cta_btn_text     → Text
    cta_btn_url      → URL
    features         → Repeater
      ├ icon         → Image  (sub-field)
      ├ title        → Text   (sub-field)
      └ description  → Textarea (sub-field)

Set Location to: Post Type == page, Page Template == templates/landing-page.php

Step 2: Create templates/landing-page.php in your theme:

    <?php /* Template Name: Landing Page */ get_header(); ?>

    <?php
    $hero_title = get_field( 'hero_title' );
    $hero_bg    = get_field( 'hero_bg_image' );
    ?>
    <section class="hero"
        <?php if ( $hero_bg ) echo 'style="background-image:url(' . esc_url( $hero_bg['url'] ) . ')"'; ?>>
        <h1><?php echo esc_html( $hero_title ); ?></h1>
        <?php the_field( 'hero_subtitle' ); ?>
        <a href="<?php the_field( 'hero_btn_url' ); ?>" class="btn">
            <?php the_field( 'hero_btn_text' ); ?>
        </a>
    </section>

    <?php if ( have_rows( 'features' ) ) : ?>
    <section class="features">
        <?php while ( have_rows( 'features' ) ) : the_row();
            $icon = get_sub_field( 'icon' ); ?>
        <div class="feature-card">
            <?php if ( $icon ) : ?>
                <img src="<?php echo esc_url( $icon['url'] ); ?>" alt="<?php echo esc_attr( $icon['alt'] ); ?>">
            <?php endif; ?>
            <h3><?php echo esc_html( get_sub_field( 'title' ) ); ?></h3>
            <p><?php echo esc_html( get_sub_field( 'description' ) ); ?></p>
        </div>
        <?php endwhile; ?>
    </section>
    <?php endif; ?>

    <?php if ( get_field( 'show_cta' ) ) : ?>
    <section class="cta">
        <h2><?php the_field( 'cta_heading' ); ?></h2>
        <a href="<?php the_field( 'cta_btn_url' ); ?>" class="btn"><?php the_field( 'cta_btn_text' ); ?></a>
    </section>
    <?php endif; ?>

    <?php get_footer(); ?>

== Dynamic FAQ ==

Create a field group with:

    faq_items     → Repeater
      ├ question  → Text       (sub-field)
      └ answer    → WYSIWYG    (sub-field)

Template:

    <?php if ( have_rows( 'faq_items' ) ) : ?>
    <section class="faq">
        <?php while ( have_rows( 'faq_items' ) ) : the_row(); ?>
        <div class="faq-item">
            <button class="faq-q" aria-expanded="false">
                <?php echo esc_html( get_sub_field( 'question' ) ); ?>
            </button>
            <div class="faq-a" hidden>
                <?php echo wp_kses_post( get_sub_field( 'answer' ) ); ?>
            </div>
        </div>
        <?php endwhile; ?>
    </section>
    <script>
    document.querySelectorAll('.faq-q').forEach(function(btn){
        btn.addEventListener('click',function(){
            var open = this.getAttribute('aria-expanded')==='true';
            this.setAttribute('aria-expanded',!open);
            this.nextElementSibling.hidden = open;
        });
    });
    </script>
    <?php endif; ?>

== Team Members Repeater ==

    team_members   → Repeater
      ├ photo      → Image
      ├ name       → Text
      ├ role       → Text
      ├ bio        → Textarea
      └ linkedin   → URL

    <?php if ( have_rows( 'team_members' ) ) : ?>
    <div class="team-grid">
        <?php while ( have_rows( 'team_members' ) ) : the_row();
            $photo = get_sub_field( 'photo' ); ?>
        <div class="team-card">
            <?php if ( $photo ) : ?>
                <img src="<?php echo esc_url( $photo['sizes']['medium'] ); ?>" alt="">
            <?php endif; ?>
            <h3><?php echo esc_html( get_sub_field( 'name' ) ); ?></h3>
            <p class="role"><?php echo esc_html( get_sub_field( 'role' ) ); ?></p>
            <p><?php echo esc_html( get_sub_field( 'bio' ) ); ?></p>
            <?php $li = get_sub_field( 'linkedin' ); ?>
            <?php if ( $li ) : ?><a href="<?php echo esc_url( $li ); ?>">LinkedIn</a><?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

== Testimonials Repeater ==

    testimonials     → Repeater
      ├ quote        → Textarea
      ├ author_name  → Text
      ├ author_title → Text
      ├ author_photo → Image
      └ rating       → Select (1/2/3/4/5)

    <?php if ( have_rows( 'testimonials' ) ) : ?>
    <div class="testimonials">
        <?php while ( have_rows( 'testimonials' ) ) : the_row();
            $photo  = get_sub_field( 'author_photo' );
            $rating = (int) get_sub_field( 'rating' ); ?>
        <div class="testimonial">
            <div class="stars"><?php echo str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ); ?></div>
            <blockquote><?php echo esc_html( get_sub_field( 'quote' ) ); ?></blockquote>
            <?php if ( $photo ) : ?>
                <img src="<?php echo esc_url( $photo['sizes']['thumbnail'] ); ?>" alt="">
            <?php endif; ?>
            <strong><?php echo esc_html( get_sub_field( 'author_name' ) ); ?></strong>
            <span><?php echo esc_html( get_sub_field( 'author_title' ) ); ?></span>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

== Pricing Plans Repeater ==

    pricing_plans  → Repeater
      ├ plan_name  → Text
      ├ price      → Text
      ├ period     → Select
      ├ features   → Textarea  (one feature per line)
      ├ btn_text   → Text
      ├ btn_url    → URL
      └ is_popular → True / False

    <?php if ( have_rows( 'pricing_plans' ) ) : ?>
    <div class="pricing-grid">
        <?php while ( have_rows( 'pricing_plans' ) ) : the_row();
            $popular  = get_sub_field( 'is_popular' );
            $features = array_filter( array_map( 'trim',
                            explode( "\n", get_sub_field( 'features' ) ) ) ); ?>
        <div class="plan<?php echo $popular ? ' popular' : ''; ?>">
            <?php if ( $popular ) echo '<span class="badge">Most Popular</span>'; ?>
            <h3><?php echo esc_html( get_sub_field( 'plan_name' ) ); ?></h3>
            <div class="price">
                <?php echo esc_html( get_sub_field( 'price' ) ); ?>
                <span><?php echo esc_html( get_sub_field( 'period' ) ); ?></span>
            </div>
            <ul><?php foreach ( $features as $f ) echo '<li>' . esc_html( $f ) . '</li>'; ?></ul>
            <a href="<?php echo esc_url( get_sub_field( 'btn_url' ) ); ?>" class="btn">
                <?php echo esc_html( get_sub_field( 'btn_text' ) ); ?>
            </a>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

== REST API ==

= GET /wp-json/dfp/v1/fields/{post_id} =

Returns all field values for a post.

Response:

    {
        "post_id": 42,
        "post_type": "page",
        "fields": {
            "hero_title": "Welcome",
            "hero_bg_image": { "id": 7, "url": "https://…", "alt": "" },
            "features": [
                { "title": "Fast", "description": "Very fast" }
            ]
        }
    }

Public posts are readable without authentication. Private posts require a logged-in user.

= POST /wp-json/dfp/v1/fields/{post_id} =

Update one or more field values. Requires edit_post capability.

Request body (JSON):

    {
        "hero_title": "New Headline",
        "show_cta": 1,
        "hero_bg_image": 7
    }

Response:

    {
        "success": true,
        "post_id": 42,
        "updated_fields": ["hero_title", "show_cta", "hero_bg_image"]
    }

JavaScript example:

    fetch('/wp-json/dfp/v1/fields/42', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpApiSettings.nonce
        },
        body: JSON.stringify({ hero_title: 'Updated' })
    }).then(res => res.json()).then(console.log);

== AJAX Endpoints ==

All AJAX endpoints require a valid nonce (dfpSettings.nonce) and manage_options capability.

* dfp_get_rule_values   — returns available values for a location rule param
* dfp_save_field_group  — save/update a field group
* dfp_toggle_group      — toggle a group active/inactive
* dfp_duplicate_group   — duplicate a group
* dfp_delete_group      — delete a group
* dfp_export_group      — download group JSON
* dfp_import_group      — import group from JSON

== Registering Custom Field Types ==

    add_action( 'dfp/register_fields', function( $dfp_fields ) {

        class My_Star_Rating_Field extends DFP_Field_Base {

            public function get_type()    { return 'star_rating'; }
            public function get_label()   { return 'Star Rating'; }
            public function get_defaults(){ return [ 'max' => 5 ]; }

            public function render_field( $field, $value ) {
                $max = isset( $field['max'] ) ? absint( $field['max'] ) : 5;
                for ( $i = 1; $i <= $max; $i++ ) {
                    echo '<label>';
                    echo '<input type="radio" name="' . esc_attr( $field['key'] ) . '" value="' . $i . '"'
                       . checked( absint( $value ), $i, false ) . '>';
                    echo ( $i <= absint( $value ) ? '★' : '☆' );
                    echo '</label>';
                }
            }

            public function render_field_settings( $field ) {
                $this->row( 'Max Stars',
                    '<input type="number" min="1" max="10" name="' . $field['key'] . '[max]"
                        value="' . esc_attr( isset( $field['max'] ) ? $field['max'] : 5 ) . '">'
                );
            }

            public function update_value( $value, $post_id, $field ) {
                return update_post_meta( $post_id, $field['key'], absint( $value ) );
            }

            public function load_value( $value, $post_id, $field ) {
                return absint( $value );
            }

            public function validate_value( $valid, $value, $field ) {
                return parent::validate_value( $valid, $value, $field );
            }
        }

        $dfp_fields->register_field_type( new My_Star_Rating_Field() );
    } );

== Hooks ==

= Actions =

* dfp/register_fields — register custom field types. Passes $dfp_fields registry instance.

= Filters =

* dfp/location/rule_params — add custom location rule params

    add_filter( 'dfp/location/rule_params', function( $params ) {
        $params['my_rule'] = 'My Custom Rule';
        return $params;
    } );

* dfp/location/rule_values — populate values for a custom rule param

    add_filter( 'dfp/location/rule_values', function( $values, $param ) {
        if ( $param === 'my_rule' ) {
            $values = [ 'a' => 'Option A', 'b' => 'Option B' ];
        }
        return $values;
    }, 10, 2 );

== Common Recipes ==

= Get field from a specific post =

    $title = get_field( 'hero_title', 5 );

= Get fields from a global settings page =

    $page_id = get_option( 'my_settings_page_id' );
    $phone   = get_field( 'contact_phone', $page_id );

= Output color as inline CSS variable =

    $color = get_field( 'brand_color' );
    if ( $color ) {
        echo '<style>:root{--brand-color:' . esc_attr( $color ) . ';}</style>';
    }

= Format a date picker value =

    $raw  = get_field( 'event_date' ); // "20260101"
    $date = $raw ? DateTime::createFromFormat( 'Ymd', $raw ) : null;
    if ( $date ) echo $date->format( 'F j, Y' ); // January 1, 2026

= Link to a related post =

    $post = get_field( 'related_post' ); // WP_Post
    if ( $post ) {
        echo '<a href="' . esc_url( get_permalink( $post ) ) . '">'
           . esc_html( $post->post_title ) . '</a>';
    }

= List taxonomy terms =

    $terms = get_field( 'featured_tags' ); // WP_Term[]
    foreach ( (array) $terms as $term ) {
        echo '<a href="' . esc_url( get_term_link( $term ) ) . '">'
           . esc_html( $term->name ) . '</a>';
    }

= Re-loop a repeater =

    if ( have_rows( 'items' ) ) {
        while ( have_rows( 'items' ) ) { the_row(); /* first pass */ }
    }
    reset_rows( 'items' );
    if ( have_rows( 'items' ) ) {
        while ( have_rows( 'items' ) ) { the_row(); /* second pass */ }
    }

= Gallery with lightbox =

    $images = get_field( 'gallery' );
    foreach ( (array) $images as $img ) {
        echo '<a href="' . esc_url( $img['url'] ) . '">'
           . '<img src="' . esc_url( $img['sizes']['medium'] ) . '"
                  alt="' . esc_attr( $img['alt'] ) . '">'
           . '</a>';
    }

= Downloadable file =

    $file = get_field( 'pdf_download' );
    if ( $file ) {
        echo '<a href="' . esc_url( $file['url'] ) . '" download>'
           . esc_html( $file['filename'] )
           . ' (' . esc_html( size_format( $file['filesize'] ) ) . ')'
           . '</a>';
    }

= Conditional section =

    if ( get_field( 'show_video_section' ) ) {
        get_template_part( 'template-parts/video' );
    }

== Frequently Asked Questions ==

= Does this conflict with ACF? =

No. All global template functions are wrapped in function_exists() checks. If ACF is active, its functions take precedence automatically. All class names use the DFP_ prefix.

= Where is field data stored? =

Each field's value is stored in wp_postmeta using the field key (e.g. field_abc123def456) as the meta key. Repeater and gallery values are stored as serialized PHP arrays.

= Can I import/export field groups? =

Yes. On the field groups list, click Export next to any group to download JSON. Use Import Field Group at the bottom of the page to import — keys are regenerated on import to prevent conflicts.

= Does it support the block editor / Gutenberg? =

Field groups appear as meta boxes below the editor. All 22 field types work through the standard WordPress meta box API.

= Can I use fields on taxonomy edit screens or user profiles? =

Location rules currently support post types, page templates, and post-level rules. Taxonomy and user profile support can be added via the location rule filters.

= How do I get the full image array vs just a URL? =

The image field returns an array by default: url, alt, width, height, sizes (thumbnail, medium, large). Set Return Format = URL to get just the URL string.

= How do I display a WYSIWYG field safely? =

Use wp_kses_post() or the_field() — never esc_html() as that would break HTML formatting.

    echo wp_kses_post( get_field( 'content' ) );
    // or:
    the_field( 'content' );

== Screenshots ==

1. Field Groups list
2. Field Builder — Fields tab
3. Field Builder — Location Rules tab
4. Field Builder — Settings tab
5. Meta box on post edit screen
6. Repeater field with multiple rows
7. Image field with media library
8. Gallery field with thumbnail grid
9. File field with file info
10. WYSIWYG editor field

== Changelog ==

= 1.0.3 =
* Product Showcase field: rebuilt admin UI using WordPress form-table layout for reliable rendering
* Product Showcase: fixed layout picker cards (compact 90px cards with SVG icons in a row)
* Product Showcase: fixed placeholder text encoding issue in category search input
* Product Showcase: accordion with per-category product tables, inline price override, product picker overlay
* Bumped asset version to force CSS/JS cache refresh

= 1.0.2 =
* Added WC Product field type (dual-pane picker, multiple/single selection)
* Added WC Category field type (hierarchical checklist with search filter)
* Added Product Showcase field type (category tabs + product selection)
* Added Page Template location rule using get_page_templates() for Elementor/Divi support
* Fixed input name attributes to use field key (save mechanism compatibility)
* Fixed update_value() in WC fields to call update_post_meta()

= 1.0.1 =
* Added Color Picker field with alpha channel support
* Added Date Picker field with configurable return formats
* Added True/False toggle UI
* Added Relationship field with dual-pane search

= 1.0.0 =
* Initial release
* 22 field types: Text, Textarea, Number, Email, URL, Password, Select, Checkbox, Radio, True/False, Post Object, Relationship, Taxonomy, User, Date Picker, Color Picker, Image, Gallery, File, WYSIWYG Editor, Repeater
* Visual drag-to-reorder field builder
* Location rules engine with OR groups / AND rules
* REST API (GET + POST /wp-json/dfp/v1/fields/{post_id})
* Import / Export field groups as JSON
* ACF-compatible template-tag API

== Upgrade Notice ==

= 1.0.3 =
Fixes Product Showcase admin UI rendering. Recommended update for all users using the Product Showcase field.

= 1.0.0 =
Initial release.
