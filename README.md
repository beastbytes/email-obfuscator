# emailobfuscator
Widget to obfuscate an email address to help prevent harvesting by spam bots.

If JavaScript is disabled on the client, the widget will output either a message or an obfuscated version of the email address.

If JavaScript is enabled the 
output is replaced with (by default) a mailto link showing (again by default) the email address. The content of the 
mailto link can be customised.

For license information see the [LICENSE](LICENSE.md) file.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist beastbytes/emailobfuscator
```

or add

```json
"beastbytes/emailobfuscator": "^1.0"
```

to the require section of your composer.json.

## Usage

Use this extension in a view.

To output the default message ("This e-mail address is protected to prevent harvesting by spam-bots")

```php
echo EmailObfuscator::widget([
    'email' => 'my.address@example.com'
]);
```

### Output

#### JavaScript Disabled

```html
<span id="w0">This e-mail address is protected to prevent harvesting by spam-bots</span>
```

#### JavaScript Enabled

```html
<span id="w0"><a href="mailto:my.address@example.com">my.address@example.com</a></span>
```

---

To output a different message set content['obfuscated']
```php
echo EmailObfuscator::widget([
    'email' => 'my.address@example.com',
    'content' => ['obfuscated' => 'Enable JavaScript to see the email address']
]);
```

### Output

#### JavaScript Disabled

```html
<span id="w0">Enable JavaScript to see the email address</span>
```

#### JavaScript Enabled

```html
<span id="w0"><a href="mailto:my.address@example.com">my.address@example.com</a></span>
```

---

To output an obfuscated version of the email address set the obfuscators: "my dot address at example dot com"

```php
echo EmailObfuscator::widget([
    'email' => 'my.address@example.com',
    'obfuscators' => [' dot ', ' at ']
]);
```

### Output

#### JavaScript Disabled

```html
<span id="w0">my dot address at example dot com</span>
```

#### JavaScript Enabled

```html
<span id="w0"><a href="mailto:my.address@example.com">my.address@example.com</a></span>
```

---

To set the content of the mailto link, set content['clear']

```php
echo EmailObfuscator::widget([
    'email' => 'my.address@example.com',
    'content' => ['clear' => 'by email']
]);
```

### Output

#### JavaScript Disabled

```html
<span id="w0">This e-mail address is protected to prevent harvesting by spam-bots</span>
```

#### JavaScript Enabled

```html
<span id="w0"><a href="mailto:my.address@example.com">by email</a></span>
```
