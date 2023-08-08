# Localized language translation made simple yet elegant

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wakjoko/localizable.svg?style=flat-square)](https://packagist.org/packages/wakjoko/localizable)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/wakjoko/localizable.svg?style=flat-square)](https://packagist.org/packages/wakjoko/localizable)

Transform any Eloquent attribute as multi language.\
I found [spatie's translation package](https://github.com/spatie/laravel-translatable) is super awesome but it's lack of query flexibility makes me ichy to build this package instead.\

## Installation

Install the package via composer:

```bash
composer require wakjoko/localizable
```

Create the table for data storage:

```bash
php artisan migrate
```

## Setting Up

Enable model localization with below:

```php
class Person extends Model
{
    use \Wakjoko\Localizable\HasLocalizable;

    public $localizable = ['name', 'title', 'gender'];
}
```
\
Use custom db connection or table name:
```env
LOCALIZABLE_DB=mysql
LOCALIZABLE_TABLE=multilanguage
```

## What would you get?

#### Preload localized language translation

```php
$person = Person::withLocalizable()->find(1);
$person = Person::withLocalizable()->first();
$persons = Person::withLocalizable()->get();
$persons = Person::withLocalizable()->paginate();
```

#### Narrow down Person results based on localized language translation

```php
// find anyone with he's name in english contains "john"
$models = Person::withLocalizable(
        attribute: 'name',
        locale: 'en',
        value: 'john'
    )
    ->get();
```

#### Creating, updating localized language translation

```php
// insert new data along with localized language translation
$person = Person::create([
    'name' => [
        'ms' => 'Nuh',
        'en' => 'Noah'
    ]
]);

// another way of creating new data
$person = new Person([
    'name' => [
        'ms' => 'Nuh',
        'en' => 'Noah'
    ]
]);
$person->save();

// change my english name
$person->name('en', 'Charlie');
$person->save();

// or update via attribute
$person->name = [
    'ms' => 'Nuh',
    'en' => 'Noah'
];
$person->save();

// or use update() instead of save()
$person->update(['name' => ['en' => 'John']]);

```

#### Switch locale

```php
$person->name               // prints Noah: use default lang in config('app.locale')
$person->setLocale('ms');   // switch language only on this model
$person->name               // prints: Nuh
$person->name('en');        // prints Noah: another way to get localized translation without changing default lang on the model
```

#### Localizable data are also inspectable in Tinker

```json
App\Models\Person {#10328
    id: 1,
    name: [
      "en" => "Noah",
      "ms" => "Nuh",
    ],
  }
```

### Testing

```bash

naah.. nothing yet haha!
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please mail to wakjoko@gmail.com instead of using the issue tracker.

## Credits

-   [Spatie Translatable](https://github.com/spatie/laravel-translatable)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
