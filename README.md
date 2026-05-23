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
16. [WooCommerce — WC Product Field](#16-woocommerce--wc-product-field)
17. [WooCommerce — WC Category Field](#17-woocommerce--wc-category-field)
18. [WooCommerce — Product Showcase Field](#18-woocommerce--product-showcase-field)

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
| **WC Product** | `WC_Product[]` or `int[]` | Select WooCommerce products |
| **WC Category** | `WP_Term[]` or `int[]` | Select product categories |
| **Product Showcase** | `array[]` | Category tabs with curated products |

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


## 16. WooCommerce — WC Product Field

Lets a content editor pick one or more WooCommerce products from a dual-pane UI (available left, selected right). Works even if WooCommerce is not installed — falls back to standard `WP_Post` objects.

### Field Settings

| Setting | Options | Default |
|---------|---------|---------|
| Multiple | On / Off | On |
| Return Format | Product Object / Product ID | Product Object |
| Product Status | Published / Any / Draft | Published |

### Admin UI

The field shows:
- **Left pane** — all products (thumbnail + title + price), with live search filter
- **Right pane** — selected products with a remove button
- Click a product on the left to move it to the right; click × on the right to deselect

### Template Usage

**Return format: Object (default)**

```php
<?php
$products = get_field( 'featured_products' ); // array of WC_Product objects

if ( $products ) : ?>
<div class="product-list">
    <?php foreach ( $products as $product ) : ?>
    <div class="product-card">
        <?php echo get_the_post_thumbnail( $product->get_id(), 'medium' ); ?>
        <h3><?php echo esc_html( $product->get_name() ); ?></h3>
        <p class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
        <a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>"
           class="btn">View Details</a>
        <a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
           class="btn btn-primary">Buy Now</a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
```

**Return format: ID**

```php
$product_ids = get_field( 'featured_products' ); // array of int

foreach ( $product_ids as $id ) {
    $product = wc_get_product( $id );
    echo esc_html( $product->get_name() );
}
```

---

## 17. WooCommerce — WC Category Field

Displays all product categories (`product_cat` taxonomy) as a hierarchical checkbox list. Supports live filtering and single/multiple selection.

### Field Settings

| Setting | Options | Default |
|---------|---------|---------|
| Multiple | On / Off | On |
| Return Format | Term Object / Term ID / Term Slug | Term Object |

### Admin UI

- Scrollable checklist with hierarchical indentation (child categories indented under parents)
- Live search filter at the top
- Product count shown next to each category name

### Template Usage

**Return format: Object (default)**

```php
<?php
$categories = get_field( 'product_categories' ); // array of WP_Term

if ( $categories ) : ?>
<ul class="category-list">
    <?php foreach ( $categories as $cat ) : ?>
    <li>
        <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
            <?php echo esc_html( $cat->name ); ?>
            <span class="count">(<?php echo absint( $cat->count ); ?>)</span>
        </a>
    </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>
```

**Return format: Slug**

```php
$slugs = get_field( 'product_categories' ); // ['mobile-computers', 'scanners']

foreach ( $slugs as $slug ) {
    $term = get_term_by( 'slug', $slug, 'product_cat' );
    if ( $term ) {
        echo esc_html( $term->name );
    }
}
```

**Query products by selected categories:**

```php
$categories = get_field( 'product_categories' ); // WP_Term objects
$cat_ids    = wp_list_pluck( $categories, 'term_id' );

$query = new WP_Query( [
    'post_type'  => 'product',
    'tax_query'  => [ [
        'taxonomy' => 'product_cat',
        'field'    => 'term_id',
        'terms'    => $cat_ids,
    ] ],
] );
```

---

## 18. WooCommerce — Product Showcase Field

The **Product Showcase** field lets a content editor build an interactive tabbed product display — like the one on WooCommerce store landing pages. Each tab is a product category with a curated list of products underneath.

**What it looks like on the frontend:**

```
[ Mobile Computers ]  [ Barcode Scanners ]  [ RFID Solutions ]  [ Printers ]
─────────────────────────────────────────────────────────────
 ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌────────────┐
 │  [thumb]   │  │  [thumb]   │  │  [thumb]   │  │  [thumb]   │
 │ Product A  │  │ Product B  │  │ Product C  │  │ Product D  │
 │ $2,499 AUD │  │ $1,899 AUD │  │  $329 AUD  │  │ $1,999 AUD │
 │[View][Buy] │  │[View][Buy] │  │[View][Buy] │  │[View][Buy] │
 └────────────┘  └────────────┘  └────────────┘  └────────────┘
```

### Step 1 — Create the Field Group

1. Go to **Dynamic Fields → Add New**
2. Set title: `Product Showcase`
3. Click **+ Add Field**
4. Set **Label** = `Product Showcase`, **Name** = `product_showcase`, **Type** = `Product Showcase`
5. Set **Location** to: Post Type == page (or whichever post type your landing page is)
6. **Save Field Group**

### Step 2 — Edit the Page

Open any page that matches the location rule. You will see a **Product Showcase** meta box with:

- **+ Add Category Tab** — adds a new tab row
- Each tab row:
  - **Category dropdown** — pick a WooCommerce product category
  - Products for that category load as clickable cards automatically
  - Click a product card to select it (blue border + checkmark); click again to deselect
  - Drag the tab handle (≡) to reorder tabs
  - Click the trash icon to remove a tab
- **Save** the page when done

### Step 3 — Return Value Structure

`get_field('product_showcase')` returns an array of tabs. Each tab is:

```php
[
    'category' => WP_Term,      // the product category term object
    'products' => WC_Product[], // array of WC_Product objects
]
```

With **Return Format = ID**:
```php
[
    'cat_id'   => 5,            // term ID
    'products' => [101, 102],   // product IDs
]
```

### Step 4 — Complete Template Implementation

Create `templates/landing-page.php` (or add to your existing template):

```php
<?php
/**
 * Template Name: Landing Page
 */
get_header();

$showcase = get_field( 'product_showcase' );
?>

<?php if ( $showcase ) : ?>

<!-- ══════════════════════════════════════════════════════════
     PRODUCT SHOWCASE — Category Tabs + Product Grid
     ══════════════════════════════════════════════════════════ -->
<section class="product-showcase">
    <div class="container">

        <!-- Tab navigation -->
        <div class="showcase-tabs" role="tablist">
            <?php foreach ( $showcase as $index => $tab ) :
                $cat = $tab['category']; // WP_Term
            ?>
            <button
                class="showcase-tab<?php echo $index === 0 ? ' active' : ''; ?>"
                role="tab"
                aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                data-tab="<?php echo absint( $cat->term_id ); ?>"
            >
                <?php echo esc_html( $cat->name ); ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Tab panels -->
        <?php foreach ( $showcase as $index => $tab ) :
            $cat      = $tab['category'];   // WP_Term
            $products = $tab['products'];   // WC_Product[]
        ?>
        <div
            class="showcase-panel<?php echo $index === 0 ? ' active' : ''; ?>"
            role="tabpanel"
            data-panel="<?php echo absint( $cat->term_id ); ?>"
        >
            <?php if ( $products ) : ?>

            <!-- Product slider / grid -->
            <div class="showcase-product-grid">
                <?php foreach ( $products as $product ) : ?>
                <div class="showcase-product-card">

                    <!-- Thumbnail -->
                    <a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>"
                       class="card-thumb">
                        <?php echo get_the_post_thumbnail(
                            $product->get_id(),
                            'woocommerce_thumbnail',
                            [ 'alt' => esc_attr( $product->get_name() ) ]
                        ); ?>
                    </a>

                    <!-- Info -->
                    <div class="card-body">
                        <h3 class="card-title">
                            <a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>">
                                <?php echo esc_html( $product->get_name() ); ?>
                            </a>
                        </h3>

                        <?php if ( $product->get_short_description() ) : ?>
                        <p class="card-desc">
                            <?php echo wp_kses_post( $product->get_short_description() ); ?>
                        </p>
                        <?php endif; ?>

                        <div class="card-price">
                            <?php echo wp_kses_post( $product->get_price_html() ); ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card-actions">
                        <a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>"
                           class="btn btn-outline">View Details</a>
                        <a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
                           class="btn btn-primary"
                           <?php echo $product->supports( 'ajax_add_to_cart' ) ? 'data-quantity="1" data-product_id="' . absint( $product->get_id() ) . '" class="btn btn-primary add_to_cart_button ajax_add_to_cart"' : ''; ?>>
                            Buy Now
                        </a>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>

            <!-- View all link -->
            <div class="showcase-view-all">
                <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="link-arrow">
                    View All <?php echo esc_html( $cat->name ); ?> &rarr;
                </a>
            </div>

            <?php else : ?>
            <p class="no-products">No products found in this category.</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

    </div>
</section>

<?php endif; ?>

<?php get_footer(); ?>
```

### Step 5 — CSS Styling

```css
/* ── Product Showcase Section ── */
.product-showcase {
    padding: 60px 0;
    background: #f8f9fa;
}

/* Tab navigation */
.showcase-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-bottom: 32px;
    border-bottom: 2px solid #e2e4e7;
}

.showcase-tab {
    padding: 12px 24px;
    border: none;
    background: none;
    font-size: 15px;
    font-weight: 600;
    color: #555;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: color .2s, border-color .2s;
}
.showcase-tab:hover    { color: #0073aa; }
.showcase-tab.active   { color: #0073aa; border-bottom-color: #0073aa; }

/* Tab panels */
.showcase-panel        { display: none; }
.showcase-panel.active { display: block; }

/* Product grid */
.showcase-product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 24px;
    margin-bottom: 24px;
}

/* Product card */
.showcase-product-card {
    background: #fff;
    border: 1px solid #e2e4e7;
    border-radius: 8px;
    overflow: hidden;
    transition: box-shadow .2s, transform .2s;
    display: flex;
    flex-direction: column;
}
.showcase-product-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,.10);
    transform: translateY(-2px);
}

.card-thumb img {
    width: 100%;
    height: 200px;
    object-fit: contain;
    padding: 16px;
    display: block;
}

.card-body {
    padding: 16px;
    flex: 1;
}
.card-title { font-size: 15px; font-weight: 700; margin: 0 0 8px; }
.card-title a { color: #1a2332; text-decoration: none; }
.card-title a:hover { color: #0073aa; }
.card-desc { font-size: 13px; color: #666; margin: 0 0 12px; line-height: 1.5; }
.card-price { font-size: 18px; font-weight: 700; color: #0073aa; }

.card-actions {
    padding: 16px;
    display: flex;
    gap: 8px;
    border-top: 1px solid #f0f0f0;
}

/* Buttons */
.btn {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    flex: 1;
    border: 2px solid transparent;
    transition: background .15s, color .15s;
}
.btn-outline {
    border-color: #0073aa;
    color: #0073aa;
    background: transparent;
}
.btn-outline:hover { background: #0073aa; color: #fff; }
.btn-primary { background: #e74c3c; color: #fff; border-color: #e74c3c; }
.btn-primary:hover { background: #c0392b; border-color: #c0392b; }

/* View all link */
.showcase-view-all { text-align: center; margin-top: 8px; }
.link-arrow {
    font-size: 14px;
    font-weight: 600;
    color: #0073aa;
    text-decoration: none;
}
.link-arrow:hover { text-decoration: underline; }

/* Responsive */
@media (max-width: 768px) {
    .showcase-product-grid { grid-template-columns: repeat(2, 1fr); }
    .showcase-tab          { padding: 10px 14px; font-size: 13px; }
}
@media (max-width: 480px) {
    .showcase-product-grid { grid-template-columns: 1fr; }
}
```

### Step 6 — JavaScript Tab Switching

```js
// Plain JS — no jQuery required
document.addEventListener( 'DOMContentLoaded', function () {

    var tabs   = document.querySelectorAll( '.showcase-tab' );
    var panels = document.querySelectorAll( '.showcase-panel' );

    tabs.forEach( function ( tab ) {
        tab.addEventListener( 'click', function () {
            var targetId = this.dataset.tab;

            // Update tabs
            tabs.forEach( function ( t ) {
                t.classList.remove( 'active' );
                t.setAttribute( 'aria-selected', 'false' );
            } );
            this.classList.add( 'active' );
            this.setAttribute( 'aria-selected', 'true' );

            // Update panels
            panels.forEach( function ( p ) { p.classList.remove( 'active' ); } );
            var panel = document.querySelector( '.showcase-panel[data-panel="' + targetId + '"]' );
            if ( panel ) { panel.classList.add( 'active' ); }
        } );
    } );
} );
```

### Return Format Reference

```php
// Default: objects
$showcase = get_field( 'product_showcase' );

foreach ( $showcase as $tab ) {
    $tab['category']  // WP_Term — $cat->term_id, $cat->name, $cat->slug, $cat->description
    $tab['products']  // WC_Product[] — see WooCommerce API for all methods
}

// WC_Product methods
$product->get_id()
$product->get_name()
$product->get_price()
$product->get_price_html()       // formatted price with currency
$product->get_short_description()
$product->get_regular_price()
$product->get_sale_price()
$product->is_on_sale()
$product->is_in_stock()
$product->get_stock_quantity()
$product->add_to_cart_url()
$product->get_sku()

// Return format: IDs only (faster, no object instantiation)
// Set "Return Format" = "IDs only" in field settings
$showcase = get_field( 'product_showcase' );
foreach ( $showcase as $tab ) {
    $tab['cat_id']    // int — term ID
    $tab['products']  // int[] — product post IDs
}
```

### Full Landing Page Field Group Setup

For a complete landing page with a product showcase, recommended field group:

| Field Label | Field Name | Type |
|-------------|-----------|------|
| Hero Title | `hero_title` | Text |
| Hero Subtitle | `hero_subtitle` | Textarea |
| Hero Background | `hero_bg_image` | Image |
| Hero Button Text | `hero_btn_text` | Text |
| Hero Button URL | `hero_btn_url` | URL |
| **Product Showcase** | **`product_showcase`** | **Product Showcase** |
| Show CTA | `show_cta` | True / False |
| CTA Heading | `cta_heading` | Text |
| CTA Button | `cta_btn_text` | Text |
| CTA URL | `cta_btn_url` | URL |

**Location Rule:** Post Type == page AND Page Template == your landing page template

This gives you full control over the landing page content — hero, product tabs, and call-to-action — all editable from the WordPress admin without touching code.

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

WooCommerce fields:
get_field( 'wc_product_field' )     → WC_Product[] or int[]
get_field( 'wc_category_field' )    → WP_Term[] or int[] or string[]
get_field( 'product_showcase' )     → [ { category, products[] }, … ]

REST GET  /wp-json/dfp/v1/fields/{post_id}
REST POST /wp-json/dfp/v1/fields/{post_id}
```

---

*Dynamic Fields Pro v1.0.3 — Built as a complete ACF alternative for WordPress.*
