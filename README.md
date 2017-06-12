## The Ebi Template Language

The Ebi template language uses basic HTML and special attributes for a simple yet powerful template language.

## The Basics of a Template

In general you write normal HTML. Data is included in the template by including it between `{...}`. Other special functionality is added via special template attributes.

## Data Interpolation

Data is included in a template by putting it between `{...}` braces. This is known as "interpolation" and there are quite a few options.

### Fields

To include a field from your data use its name.

```html
<p>Hello {firstName} {lastName}.</p>
```

This will include the "firstName" and "lastName" database keys. You can access deeply nested arrays by separating field names with dots.

```html
<p>Hello {user.firstName} {user.lastName}.</p>
```

### Functions

### Unescaping Data

All variables are HTML escaped by default. If you want to return unescaped HTML, you can use the **unescape** function.

```html
<p>{unescape(bodyHtml)}</p>
```

## Template Attributes

### bi-if

Only display an element if the condition is true.

```html
<p bi-if="empty(items)">
There are no items!
</p>
```

```php
if (empty($data['items'])) {
    echo "<p>There are no items!</p>";
}
```

### bi-else

Add an else element in conjunction with an if element.

```html
<div bi-if="signedIn">
    Welcome!
</div>
<div bi-else>
    Sign in to participate.
</div>
```

```php
if ($data['signedIn']) {
    echo '<div>Welcome!</div>';
} else {
    echo '<div>Sign in to participate.</div>';
}
```

### bi-each

Loop over elements.

```html
<ul bi-each="people">
    <li>Hi {first} {last}!</li>
</ul>
```

```php
echo '<ul>';
foreach ($data['people'] as $data1) {
    echo 'Hi ',
        $this->escape($data1['first']),
        ' ',
        $this->escape($data1['last']);
}
echo '</ul>';
```

### bi-as

Name the iterator element so that you can still reference the parent.

```html
<ul bi-each="comments" bi-as="i comment">
    <li>{name}: {comment.body} #{i}</li>
</ul>
```

```php
echo '<ul>';
foreach ($conext['comments'] as $i1 => $data1) {
    echo '<li>',
        $this->escape($data['name']),
        ': ',
        $this->escape($data1['body']),
        ' #',
        $this->escape($i1)
        '</li>';
}
echo '</ul>';
```

### bi-empty

Specify a template when there are no items.

```html
<ul bi-each="messages">
    <li>{body}</li>
    <li bi-empty>There are no messages.</li>
</ul>
```

```php
echo '<ul>';
if (empty($data['messages'])) {
    echo '<li>There are no messages.</li>';
} else {
    foreach ($data['message'] as $i1 => $data1) {
        echo '<li>',
            $this->escape($data1['body']),
            '</li>';
    }
}
echo '</ul>';
```

### bi-with

Pass an item into a template.

```html
<div bi-with="user">
    Hello {name}.
</div>
```

```php
$data1 = $data['user'];
echo '<div>',
    'Hello ',
    $this->escape($data1['name']),
    '</div>';
```

### bi-literal

Don't parse templates within a literal.

```html
<code bi-literal>Hello <b bi-literal>{username}</b></code>
```

```php
echo '<code>Hello <b bi-literal>{username}</b></code>';
```

### bi-x

Sometimes you will want to use an ebi attribute, but don't want to render an HTML tag. In this case you can use the **bi-x** tag which will only render its contents.

```html
<bi-x bi-if="signedIn">Welcome back</bi-x>
```

```php
if ($props['signedIn']) {
  echo 'Welcome back';
}
```

## Components

Components are a powerful part of Ebi. With components you can make re-usable templates that can be included in other templates. Here are some component basics:

- Each template is a component. You can declare additional components in a template too.
- Components are lowercase. It is recommended that you use dashes to separate words in component names. Make sure to name your template files in lowercase to avoid issues with case sensitive file systems.
- Components are used by declaring an HTML element with the component's name. Components create custom tags!
- You can pass data into components with contributes. If you want to pass all of the current template's data into a component use the `bi-with` attribute.

### bi-component

Define a component that can be used later in the template.

```html
<time bi-component="long-date" datetime="{dateFormat(date, 'c')}">{dateFormat(date, 'r')}</time>

<long-date date="{dateInserted}" />
```

```php
$this->register('long-date', function ($props) {
    echo '<time datetime="',
        htmlspecialchars(dateFormat($props['date'], 'c')),
        '">',
        htmlspecialchars(dateFormat($props['date'], 'r')),
        '</time>';
});

$this->render('long-date', ['date' => $data['dateInserted']]);
```

Components must begin with a capital letter or include a dash or dot. Otherwise they will be rendered as normal HTML tags.

### Component Data

By default, components inherit the current scope's data. There are a few more things you can do to pass additional data into a component.

#### Pass Data Using `bi-with`???

If you want to pass data other than the current context into a component you use the `with` attribute.

```html
<div class="post post-commment" bi-component="Comment">
  <img src="{author.photoUrl}" /> <a href="author.url">{author.username}</a>

  <p>{unescape(body)}</p>
</div>

<Comment bi-with="lastComment" />
```

### bi-child and bi-block

You can define custom content elements within a component with blocks. An unnamed block will uses the same tag it's declared in. If you name a block then the name becomes its tag name.

```html
<!-- Declare the layout component. -->
<html bi-component="layout">
  <head><title bi-child="title" /></head>
  <body>
    <h1 bi-child="title" />
    <div class="content" bi-child="content" />
  </body>
</html>

<!-- Use the layout component. -->
<layout>
  <bi-x bi-block="title">Hello world!</bi-x>
  <p bi-block="content">When you put yourself out there you will always do well.</p>
</layout>
```

The blocks get inserted into the component when it is used.

```html
<html>
  <head><title>Hello world!</title>
  <body>
    <h1>Hello world!</title>
    <div class="content"><p>When you put yourself out there you will always do well.</p></div>
  </body>
</html>
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

### HTML Comments

Any HTML comments that you declare in the template will be added as PHP comments in the compiled template function. This is useful for debugging or for static template generation.

```html
<!-- Do something. -->
<p>wut!?</p>
```

```php
return function ($data) {
    // Do something.
    echo '<p>wut!?</p>';
};
```

## Using Ebi in Code

The **Ebi** class is used to compile and render Ebi templates. You should only need one instance of the class to render any number of templates.

### Basic usage.

```php
$ebi = new Ebi(
    new FilesystemLoader('/path/to/templates'),
    '/path/to/cache'
);

$ebi->write('component', $data);
```

In this example an **Ebi** object is constructed and a basic component is written to the output.
