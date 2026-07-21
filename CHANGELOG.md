# Changelog

## 2026-07-21: v1.4.0
#### Added
- `minion-modules:cache` / `minion-modules:clear` commands. Caches the resolved app/Modules
  manifest (Views namespaces, Config/Policies/Events-Listeners/Shortcodes file lists) to
  `bootstrap/cache/minion_modules.php` so `boot()` skips scandir()/is_dir() across every
  module on every request. Falls back to the previous live-scan behaviour when the cache
  hasn't been built (e.g. local dev), so this is non-breaking.
#### Why
- On a 34-module app, uncached `boot()` was measured costing ~390ms per request (repeated
  filesystem scans), the single largest contributor to Laravel's bootstrap time.

## 2026-04-27: v1.3.7
#### Changed
- Updated vendor files
- 
## 2025-09-24: v1.3.6
#### Changed
- Updated PHP version
- Fix: Don't load routes if routes are cached. 

## 2023-02-03: v1.3.5
#### Changed
- Updated PHP version

## 2023-02-03: v1.3.4
#### Changed
- Route namespaces removed. Adopt class based routes.

## 2023-02-03: v1.3.3
#### Changed
- Updated requirements to allow for greater versions of PHP and Laravel.

## 2021-06-27: v1.3.2
#### Changed
- Updated vendor files
- Include php version 8.0
#### IMPORTANT
- This is likely the last update. Migration to nwidart/laravel-modules is suggested.

## 2021-02-16: v1.3.1
#### Changed
- Updated vendor files
- Now requires laravel ^8.0

## 2021-01-19: v1.3.0
#### Changed - REMOVED
- Updated vendor files
- Now requires laravel ^8.0
- Put the requirement in the wrong spot, removing this version.

## 2021-10-19: v1.2.1
#### Changed
- updated vendor files

## 2020-09-02: v1.2.0
#### Changed
- updated vendor files
#### Added
- Include Console routes

## 2020-05-22: v1.1.4
#### Changed
- Unit tests fail because it includes multiple times.

## 2020-05-19: v1.1.3
#### Changed
- issue with app_path called twice

## 2020-05-19: v1.1.2
#### Changed
- issue with registration order

## 2020-05-19: v1.1.1
#### Added
- Enable more directives than just the hardcoded ones

## 2020-05-19: v1.1.0
#### Changed
- Update to Laravel 7.0
- minor optimizations

## 2018-10-23: v1.0.0
Initial commit
