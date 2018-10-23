# Module Factory
              
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=for-the-badge)](LICENSE.md)

## Introduction

Module Factory is a simple package to organize Laravel into modules. It's fine for small projects but large projects can get 
unwieldy with the number of files. This package allows you to structure the files into modules with a similar folder layout 
as Laravel itself. 

```
app
    -Http
    -Modules
        -FullComponent
            -Config
                setup.php
            -Controllers
                PageController.php
                AdminController.php
            -Events
                -Listeners
                    PageSave.php
            -Policies
                PagePolicy.php
            -Routes
                web.php
                api.php
            -Shortcodes (optional)
                page-list.php
            -Views
                page.blade.php
                index.blade.php
        -PartialComponent
            -Controllers
                ShortcodeController.php
            -Shortcodes (optional)
                just-shortcode.php
            -Views
                list.blade.php
``` 

