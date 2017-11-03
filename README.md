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

*You might be tempted to put a dash in your field names. However this will not work as expected because the field names are interpreted as expressions. So for example {is-on} will be interpreted as "is" minus "on"!*

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
| Arithmetic | +,-,&ast;,/,%,&ast;&ast; |
| Bitwise | &,&#124;,^ |
| Comparison | ==,===,!=,!==,<,>,<=,>=,matches |
| Logical | &&,&#124;&#124;,! |
| String Concatenation | ~ |
| Array | in, not in |
| Range | .. |
| Ternary | cond ? 'yes' : 'no'<br>cond ?: 'no'<br>cond ? 'yes' |

### Functions

Functions are called using the `functionName()` syntax. Ebi provides a set of default functions that map to PHP's standard library.

| Function          | Description | PHP Function |
|------------------ | ----------- | ------------ |
| abs               | Absolute value of a number. | [abs](https://secure.php.net/manual/en/function.abs.php)
| arrayColumn       | Return the values from a single column in the input array. | [array_column](https://secure.php.net/manual/en/function.array-column.php)
| arrayKeyExists    | Checks if the given key or index exists in the array. | [array_key_exists](https://secure.php.net/manual/en/function.array-key-exists.php)
| arrayKeys         | Return the keys of an array. | [array_keys](https://secure.php.net/manual/en/function.array-keys.php)
| arrayMerge        | Merge one or more arrays. | [array_merge](https://secure.php.net/manual/en/function.array-merge.php)
| arrayMergeRecursive | Merge two or more arrays recursively. | [array_merge_recursive](https://secure.php.net/manual/en/function.array-merge-recursive.php)
| arrayReplace      | Replaces elements from passed arrays into the first array. | [array_replace](https://secure.php.net/manual/en/function.array-replace.php)
| arrayReplaceRecursive | Replaces elements from passed arrays into the first array recursively. | [array_replace_recursive](https://secure.php.net/manual/en/function.array-replace-recursive.php)
| arrayReverse      | Return an array with elements in reverse order. | [array_reverse](https://secure.php.net/manual/en/function.array-reverse.php)
| arrayValues       | Return all the values of an array. | [array_values](https://secure.php.net/manual/en/function.array-values.php)
| base64Encode      | Encodes data with MIME base64. | [base64_encode](https://secure.php.net/manual/en/function.base64-encode.php)
| ceil              | Round fractions up. | [ceil](https://secure.php.net/manual/en/function.ceil.php)
| componentExists   | Checks if the given component name exists. |
| count             | Count the number of items in an array. | [count](https://secure.php.net/manual/en/function.count.php)
| empty             | Check to see if a string or array is empty. | [empty](https://secure.php.net/manual/en/function.empty.php)
| floor             | Round fractions down. | [floor](https://secure.php.net/manual/en/function.floor.php)
| formatDate        | Format a date. | [date_format](https://secure.php.net/manual/en/function.date-format.php)
| formatNumber      | Format a number with grouped thousands. | [number_format](https://secure.php.net/manual/en/function.number-format.php)
| hasChildren       | Checks if the component has children passed to it. Pass the name of a block to test for that specific child.
| htmlEncode        | Convert special characters to HTML entities. | [htmlspecialchars](https://secure.php.net/manual/en/function.htmlspecialchars.php)
| isArray           | Finds whether a variable is an array. | [is_string](http://php.net/manual/en/function.is-array.php)
| isBool            | Finds out whether a variable is a boolean. | [is_bool](http://php.net/manual/en/function.is-bool.php)
| isInt             | Find whether the type of a variable is integer | [is_int](http://php.net/manual/en/function.is-int.php)
| isScalar          | Finds whether a variable is a scalar. | [is_scalar](http://php.net/manual/en/function.is-scalar.php)
| isString          | Find whether the type of a variable is string. | [is_string](http://php.net/manual/en/function.is-string.php)
| join              | Join the elements of an array into a string. | [implode](https://secure.php.net/manual/en/function.implode.php)
| lcase             | Lowercase a string. | [strtolower](https://secure.php.net/manual/en/function.strtolower.php), [mb_strtolower](https://secure.php.net/manual/en/function.mb-strtolower.php)
| lcfirst           | Lowercase the first letter of a word. | [lcfirst](https://secure.php.net/manual/en/function.lcfirst.php)
| ltrim             | Left trim a string. | [ltrim](https://secure.php.net/manual/en/function.ltrim.php)
| max               | Find highest value. | [max](https://secure.php.net/manual/en/function.max.php)
| min               | Find the lowest value. | [min](https://secure.php.net/manual/en/function.min.php)
| queryEncode       | Generate a URL-encoded query string. | [http_build_query](https://secure.php.net/manual/en/function.http-build-query.php)
| round             | Round a number. | [round](https://secure.php.net/manual/en/function.round.php)
| rtrim             | Right trim a string. | [rtrim](https://secure.php.net/manual/en/function.rtrim.php)
| sprintf           | Return a formatted string. | [sprintf](https://secure.php.net/manual/en/function.sprintf.php)
| strlen            | Return the length of a string. | [strlen](https://secure.php.net/manual/en/function.strlen.php), [mb_strlen](https://secure.php.net/manual/en/function.mb-strlen.php)
| substr            | Return a part of a string. | [substr](https://secure.php.net/manual/en/function.substr.php), [mb_substr](https://secure.php.net/manual/en/function.mb-substr.php)
| trim              | Trim a string. | [trim](https://secure.php.net/manual/en/function.trim.php)
| ucase             | Uppercase a string. | [strtoupper](https://secure.php.net/manual/en/function.strtoupper.php), [mb_strtoupper](https://secure.php.net/manual/en/function.mb-strtoupper.php)
| ucfirst           | Uppercase the first letter of a word. | [ucfirst](https://secure.php.net/manual/en/function.ucfirst.php)
| ucwords           | Uppercase the first letter in each word of a string. | [ucwords](https://secure.php.net/manual/en/function.ucwords.php)
| urlEncode         | URL-encode according to RFC 3986. | [rawurlencode](https://secure.php.net/manual/en/function.rawurlencode.php)

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

### x-tag

Sometimes you want to dynamically determine the name of a tag. That's where the the `x-tag` attribute comes in.

```html
<x x-tag="'h'~level">{heading}</x>
```

```php
echo '<h'.$props['level'].'>',
    $this->escape($props['heading']),
    '</h'.$props['level'].'>';
```

## Template Tags

Most of Ebi's functionality uses special attributes. However, there are a couple of special tags supported.

### The `<script type="ebi">` Tag

Usually, you write expressions by enclosing them in braces (`{..}`). However, braces don't themselves allow brace characters. They also don't allow multi-line expressions. When you have such an expression you can instead enclose it in a `<script tpye="ebi">` tag.

```html
<script type="ebi">
  join(
    "|",
    [1, 2, 3]
  )
</script>
```

```php
echo $this->escape(join('|', [1, 2, 3]);
```

#### `<script x-unescape>`

If you don't want to escape the output in an `<script>` tag then add the `x-unescape` attribute. You don't have to include the `type="ebi"` in this case.

```html
<script x-unescape>join('>', [1, 2, 3])</script>
```

```php
echo join('>', [1, 2, 3]);
```

#### `<script x-as="...">`

You can also use the `<script>` tag with an `x-as` attribute to create an expression variable that can be used later in the template. You don't have to include the `type="ebi"` in this case.

```html
<script x-as="title">trim(ucfirst(sentence))</script>
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

*Tip: You can use the **hasChildren()** function to determine if a particular block has been passed to your component.*

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

### Attribute expressions

When you specify an attribute value as an expression then the attribute will render differently depending on the value.

| Value | Behavior |
| ----- | -------- |
| true | Renders just the attribute. |
| false, null | Won't render the attribute. |
| aria-* attribute | Values of true or false render as string values. |
| other values | render as normal attribute definitions. |

### Examples

The following templates:

```html
<input type="checkbox" checked="{true}" />
<input type="checkbox" checked="{false}" />
<span role="checkbox" aria-checked="{true}" />
```

Will result in the following output:

```html
<input type="checkbox" checked />
<input type="checkbox" />
<span role="checkbox" aria-checked="true" />
```

### CSS class attributes

When you assign a css class with data you can pass it an array or an object.

#### Array class attributes

```html
<p class="{['comment', 'is-default']}">Hello</p>
```

All elements of the array are rendered as separate classes.

```html
<p class="comment is-default">Hello</p>
```

#### Object class attributes

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
