# The Ebi Template Language

[![Build Status](https://img.shields.io/travis/vanilla/ebi.svg?style=flat)](https://travis-ci.org/vanilla/ebi)
[![Coverage](https://img.shields.io/scrutinizer/coverage/g/vanilla/ebi.svg?style=flat)](https://scrutinizer-ci.com/g/vanilla/ebi/)
[![Packagist Version](https://img.shields.io/packagist/v/vanilla/ebi.svg?style=flat)](https://packagist.org/packages/vanilla/ebi)
![MIT License](https://img.shields.io/packagist/l/vanilla/ebi.svg?style=flat)
[![CLA](https://cla-assistant.io/readme/badge/vanilla/ebi)](https://cla-assistant.io/vanilla/ebi)


The Ebi template language uses basic HTML and special attributes for a simple yet powerful template language.

## The Basics of a Template

In general you write normal HTML. Data is included in the template by including it between `{...}`. Other special functionality is added via special template attributes.

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

### Meta

You can add global meta data to all templates which are then accessed by putting an `@` sign before a variable name.

```html
<h1>{@title}</h1>
```

```php
$ebi = new Ebi(...);
$ebi->setMeta('title', 'Welcome to the Page');

$ebi->write(...);
```

The meta array is a good place to put configuration information that is separate from template data. Since it is global to all components you can access it from within any component regardless of scope.

### Operators

When writing fields you aren't limited to just field names. A fairly rich expression syntax is supported.

| Type | Operators |
| ---- | --------- |
| Arithmetic | `+,-,*,/,%,**` |
| Bitwise | `|,&,^` |
| Comparison | `==,===,!=,!==, <,>,<=,>=,matches (regex)` |
| Logical | `||,!,&&` |
| String Concatenation | `~` |
| Array | `in, not in` |
| Range | `..` |
| Ternary | `cond ? 'yes' : no`,`cond ?: 'no'`,`cond ? 'yes' |

### Functions

Functions are called using the `functionName()` syntax. The following functions are included by default:

| Function   | Description
|------------|-------------
| count      | Count the number of items in an array. 
| empty      | Check to see if a string or array is empty.
| formatDate | Format a date.
| join       | Join the elements of an array into a string. 
| lcase      | Lowercase a string.
| lcfirst    | Lowercase the first letter of a word.
| ltrim      | Left trim a string.
| rtrim      | Right trim a string.
| sprintf    | Return a formatted string.
| substr     | Return a part of a string.
| trim       | Trim a string.
| ucase      | Uppercase a string.
| ucfirst    | Uppercase the first letter of a word.
| ucwords    | Uppercase the first letter in each word of a string.

### Literals

You can include literals in expressions too.

| Type | Notation | Example |
| ---- | -------- | ------- |
| string | Enclose in single or double quotes. | 'hello' |
| number | Write the number without quotes. | 123 |
| array | Use JSON notation. | [1, 2, 3] |
| object | Use JSON notation without quoted keys. | { foo: 'bar' } |
| boolean | Use the `true` and `false` constants. | true |
| null | Use `null` constant. | null |

### Unescaping Data

All variables are HTML escaped by default. If you want to return unescaped HTML, you can use the **unescape** function.

```html
<p>{unescape(bodyHtml)}</p>
```

## Template Attributes

Most of Ebi's functionality is accessed using template attributes. These are HTML style attributes that you add to any tag in your template to add logic. All of Ebi's attributes start with an `x-` prefix to help you differentiate between Ebi attributes and regular HTML attributes.

*The letter "X" was chosen to mean "extended attribute" and was inspired by the same prefix in HTTP headers.*

### x-if

Only display an element if the condition is true.

```html
<p x-if="empty(items)">
There are no items!
</p>
```

```php
if (empty($props['items'])) {
    echo "<p>There are no items!</p>";
}
```

### x-else

Add an else element in conjunction with an if element.

```html
<div x-if="signedIn">
    Welcome!
</div>
<div x-else>
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

### x-each

Loop over elements.

```html
<ul x-each="people">
    <li>Hi {first} {last}!</li>
</ul>
```

```php
echo '<ul>';
foreach ($props['people'] as $props1) {
    echo 'Hi ',
        $this->escape($props1['first']),
        ' ',
        $this->escape($props1['last']);
}
echo '</ul>';
```

#### x-each x-as

Name the iterator element so that you can still reference the parent.

```html
<ul x-each="comments" x-as="i comment">
    <li>{name}: {comment.body} #{i}</li>
</ul>
```

```php
echo '<ul>';
foreach ($conext['comments'] as $i1 => $props1) {
    echo '<li>',
        $this->escape($props['name']),
        ': ',
        $this->escape($props1['body']),
        ' #',
        $this->escape($i1)
        '</li>';
}
echo '</ul>';
```

*Tip: If you want to access the key of an array, but still want to access its values without dot syntax then you can use `x-as="key this"`.*

### x-empty

Specify a template when there are no items.

```html
<ul x-each="messages">
    <li>{body}</li>
    <li x-empty>There are no messages.</li>
</ul>
```

```php
echo '<ul>';
if (empty($props['messages'])) {
    echo '<li>There are no messages.</li>';
} else {
    foreach ($props['message'] as $i1 => $props1) {
        echo '<li>',
            $this->escape($props1['body']),
            '</li>';
    }
}
echo '</ul>';
```

### x-with

Pass an item into a template.

```html
<div x-with="user">
    Hello {name}.
</div>
```

```php
$props1 = $props['user'];
echo '<div>',
    'Hello ',
    $this->escape($props1['name']),
    '</div>';
```

#### x-with x-as

You can give an alias to the data referenced with `x-with` so that you can still access the parent data within the block. A good use for this is for performing a calculation on some data and assigning it to a variable.

```html
<x x-with="trim(ucfirst(sentence))" x-as="title"><h1 x-if="!empty(title)">{title}</h1></x>
```

```php
$props1 = trim(ucfirst($props['sentence']));
if (!empty($props1)) {
    echo $this->escape($props1);
}
```

### x-literal

Don't parse templates within a literal.

```html
<code x-literal>Hello <b x-literal>{username}</b></code>
```

```php
echo '<code>Hello <b x-literal>{username}</b></code>';
```

## Template Tags

Most of Ebi's functionality uses special attributes. However, there are a couple of special tags supported.

### The `<x-expr>` Tag

Usually, you write expressions by enclosing them in braces (`{..}`). However, braces don't themselves allow brace characters. They also don't allow multi-line expressions. When you have such an expression you can instead enclose it in an `x-expr` tag.

```html
<x-expr>
  join(
    "|",
    [1, 2, 3]
  )
</x-expr>
```

```php
echo $this->escape(join('|', [1, 2, 3]);
```

#### `<x-expr x-unescape>`

If you don't want to escape the output in an `<x-expr>` tag then add the `x-unescape` attribute.

```html
<x-expr x-unescape>join('>', [1, 2, 3])</x-expr>
```

```php
echo join('>', [1, 2, 3]);
```

#### `<x-expr x-as="...">`

You can also use the `<x-expr>` tag with an `x-as` attribute to create an expression variable that can be used later in the template.

```html
<x-expr x-as="title">trim(ucfirst(sentence))</x-expr>
<h1 x-if="!empty(title)">{title}</h1>
```

```php
$title = trim(ucfirst($props['sentence']));
if (!empty($title) {
    echo '<h1>',
        $this->escape($title),
        '</h1>';
}
```

### The `<x>` Tag

Sometimes you will want to use an ebi attribute, but don't want to render an HTML tag. In this case you can use the `x` tag which will only render its contents.

```html
<x x-if="signedIn">Welcome back</x>
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
- You can pass data into components with contributes. If you want to pass all of the current template's data into a component use the `x-with` attribute.
- It is strongly recommended you don't name your components with an `x-` prefix as that may be used for future functionality.

### x-component

Define a component that can be used later in the template.

```html
<time x-component="long-date" datetime="{date(date, 'c')}">{date(date, 'r')}</time>

<long-date date="{dateInserted}" />
```

```php
$this->register('long-date', function ($props) {
    echo '<time datetime="',
        htmlspecialchars(date($props['date'], 'c')),
        '">',
        htmlspecialchars(date($props['date'], 'r')),
        '</time>';
});

$this->render('long-date', ['date' => $props['dateInserted']]);
```

Components must begin with a capital letter or include a dash or dot. Otherwise they will be rendered as normal HTML tags.

### Component Data

By default, components inherit the current scope's data. There are a few more things you can do to pass additional data into a component.

#### Pass Data Using `x-with`

If you want to pass data other than the current context into a component you use the `x-with` attribute.

```html
<div class="post post-commment" x-component="Comment">
  <img src="{author.photoUrl}" /> <a href="author.url">{author.username}</a>

  <p>{unescape(body)}</p>
</div>

<Comment x-with="lastComment" />
```

### x-children and x-block

You can define custom content elements within a component with blocks. An unnamed block will use the same tag it's declared in.

```html
<!-- Declare the layout component. -->
<html x-component="layout">
  <head><title x-children="title" /></head>
  <body>
    <h1 x-children="title" />
    <div class="content" x-children="content" />
  </body>
</html>

<!-- Use the layout component. -->
<layout>
  <x x-block="title">Hello world!</x>
  <p x-block="content">When you put yourself out there you will always do well.</p>
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

### x-include

Sometimes you want to include a component dynamically. In this case you can use the `x-include` attribute.

```html
<div x-component="hello">Hello {name}</div>
<div x-component="goodbye">Goodbye {name}</div>

<x x-include="salutation" />
```

```php
$this->register('hello', function ($props) {
    echo 'Hello ',
        $this->escape($props['name']);
});

$this->register('goodbye', function ($props) {
    echo 'Goodbye ',
        $this->escape($props['name']);
});

$this->write($props['salutation'], $props);
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
function ($props) {
    // Do something.
    echo '<p>wut!?</p>';
};
```

## Using Ebi in Code

The **Ebi** class is used to compile and render Ebi templates. You should only need one instance of the class to render any number of templates.

### Basic Usage

```php
$ebi = new Ebi(
    new FilesystemLoader('/path/to/templates'),
    '/path/to/cache'
);

$ebi->write('component', $props);
```

In this example an **Ebi** object is constructed and a basic component is written to the output.
