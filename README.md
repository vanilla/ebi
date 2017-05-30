## The Ebi Template Language

*HTML Attribute Template Markup Language*

The Ebi templating language uses basic HTML and special attributes for a simple yet powerful templating language.

## Basic Templating

In general you write normal HTML. Data is included in the template by including it between `{...}`. Other special functionality is added via special template attributes.

## Template Attributes

### if

Only display an element if the condition is true.

```html
<p if="empty(items)">
There are no items!!!
</p>
```

```php
if (empty($props['items'])) {
    echo "<p>\nThere are no items!!!\n</p>";
}
```

### else

Add an else element in conjunction with an if element.

```html
<div if="signedIn">
  Welcome!
</div>
<div else>
  Sign in to participate.
</div>
```

```php
if ($props['signedIn']) {
    echo '<div>Welcome!</div>';
} else {
    echo '<div>Sign in to participate.</div>';
}
```

### each

Loop over elements.

```html
<ul each="people">
  <li>Hi {first} {last}!</li>
</ul>
```

```php
echo '<ul>';
foreach ($props['people'] as $props1) {
    echo 'Hi ',
        htmlspecialchars($props1['first']),
        ' ',
        htmlspecialchars($props1['last']);
}
echo '</ul>';
```

### as

Name the iterator element so that you can still reference the parent.

```html
<ul each="comments" as="comment">
  <li>{name}: {comment.body}</li>
</ul>
```

```php
echo '<ul>';
foreach ($conext['comments'] as $i1 => $props1) {
    echo '<li>',
        htmlspecialchars($props['name']),
        ': ',
        htmlspecialchars($props1['body']),
        '</li>';
}
echo '</ul>';
```

### empty

Specify a template when there are no items.

```html
<ul each="messages">
  <li>{body}</li>
  <li empty>There are no messages.</li>
</ul>
```

```php
echo '<ul>';
if (empty($props['messages'])) {
    echo '<li>There are no messages.</li>';
} else {
    foreach ($props['message'] as $i1 => $props1) {
        echo '<li>',
            htmlspecialchars($props1['body']),
            '</li>';
    }
}
echo '</ul>';
```

### with

Pass an item into a template.

```html
<div with="user">
  Hello {username}.
</div>
```

```php
echo '<div>',
    'Hello ',
    htmlspecialchars($props['user']['username']);
```

### literal

Don't parse templates within a literal.

```html
<code literal>Hello <b literal>{username}</b></code>
```

```php
echo '<code>Hello <b literal>{username}</b></code>';
```

### component

```html
<time component="long-date" datetime="{dateFormat(this, 'c')}">{dateFormat(this, 'r')}</time>

<long-date props="dateInserted" />
```

```php
$this->registerComponent('LongDate', function ($props) {
    echo '<time datetime=",
        htmlspecialchars(dateFormat($props, 'c')),
        '">',
        dateFormat($props, 'r'),
        '</time>';
});

$this->renderComponent('LongDate', $props['dateInserted']);
```

## HTML Utilities

### CSS class attributes

When you assign a css class with data you can pass it an array or an object.

### Array class attributes

```html
<p class="{['comment', 'is-default']}">Hello</p>
```

All elements of the array are rendered as separate classes.

```html
<p class="comment is-default">Hello</p>
```

### Object class attributes

```html
<p class="{{comment: true, 'is-default': isDefault }}">Hello</p>
```

When passing an object as the class attribute the keys define the class names and the values define whether they should be included. In this way you can enable/disable CSS classes with logic from the template's data.

Note the double braces in the above example. The first brace tells us we are using variable interpolation and the second brace wraps the object in JSON notation.

### Whitespace

Whitespace around block level elements is trimmed by default resulting in more compact output of your HTML.

### HTML comments

Any HTML comments that you declare in the template will be added as PHP comments in the compiled template function. This is useful for debugging or for static template generation.

```html
<!-- Do something. -->
<p>wut!?</p>
```

```php
return function ($props) {
    // Do something.
    echo '<p>wut!?</p>';
};
```
