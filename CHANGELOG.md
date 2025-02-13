# Yii Cache Change Log

## 3.0.1 under development

- New #132: Add interface `SerializerInterface` for data serialization, and `PhpSerializer` implementation (@Gerych1984)
- Chg #139: Make `normalize()` method static in `CacheKeyNormalizer` class (@terabytesoftw)
- Enh #142: Minor refactoring: explicitly mark parameters as nullable (@terabytesoftw)
- Chg #146: Change PHP constraint in `composer.json` to `~8.0.0 || ~8.1.0 || ~8.2.0 || ~8.3.0 || ~8.4.0` (@vjik)
- Chg #147: Bump minimal PHP version to 8.1 (@vjik)

## 3.0.0 February 15, 2023

- Chg #117: Adapt configuration group names to Yii conventions (@vjik)

## 2.0.0 June 29, 2022

- Chg #103: Raise the minimum `psr/simple-cache` version to `^2.0|^3.0` and the minimum PHP version to `^8.0` (@vjik)

## 1.0.1 March 23, 2021

- Chg: Adjust config for new config plugin (@samdark)

## 1.0.0 February 02, 2021

- Initial release.
