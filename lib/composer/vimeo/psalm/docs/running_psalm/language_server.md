# Using Psalm’s Language Server

Psalm now has built-in Language Server Compatibility support so you can run it in your favourite IDE.

It currently supports diagnostics (i.e. finding errors and warnings), go-to-definition and hover.

It works well in a variety of editors (listed alphabetically):

## Emacs

I got it working with [eglot](https://github.com/joaotavora/eglot)

This is the config I used:

```
(when (file-exists-p "vendor/bin/psalm-language-server")
  (progn
    (require 'php-mode)
    (require 'eglot)
    (add-to-list 'eglot-server-programs '(php-mode . ("php" "vendor/bin/psalm-language-server")))
    (add-hook 'php-mode-hook 'eglot-ensure)
    (advice-add 'eglot-eldoc-function :around
                (lambda (oldfun)
                  (let ((help (help-at-pt-kbd-string)))
                    (if help (message "%s" help) (funcall oldfun)))))
    )
  )
```

## PhpStorm

I've got it working with `gtache/intellij-lsp` plugin ([Jetbrains-approved version](https://plugins.jetbrains.com/plugin/10209-lsp-support), [latest version](https://github.com/gtache/intellij-lsp/releases/tag/v1.6.0)).

Setup is done via a GUI.

When you install the plugin, you should see a "Language Server Protocol" section under the "Languages & Frameworks" tab.

In the "Server definitions" tab you should add a definition for Psalm:

 - Select `Executable`
 - Extension: `php`
 - Path: `<path-to-php-binary>` e.g. `/usr/local/bin/php` or `C:\php\php.exe`
    - this should be an absolute path, not just `php`
 - Args: `vendor/bin/psalm-language-server` (on Windows use `vendor/vimeo/psalm/psalm-language-server`, or for a 'global' install '%APPDATA%' + `\Composer\vendor\vimeo\psalm\psalm-language-server`, where the '%APPDATA%' environment variable is probably something like `C:\Users\<homedir>\AppData\Roaming\`)

In the "Timeouts" tab you can adjust the initialization timeout. This is important if you have a large project. You should set the "Init" value to the number of milliseconds you allow Psalm to scan your entire project and your project's dependencies. For opening a couple of projects that use large PHP frameworks, on a high end business laptop, try `240000` milliseconds for Init.

## Sublime Text

I use the excellent Sublime [LSP plugin](https://github.com/tomv564/LSP) with the following config:

```json
        "psalm":
        {
            "command": ["php", "vendor/bin/psalm-language-server"],
            "scopes": ["source.php", "embedding.php"],
            "syntaxes": ["Packages/PHP/PHP.sublime-syntax"],
            "languageId": "php"
        }
```

## Vim & Neovim

**ALE**

[ALE](https://github.com/w0rp/ale) has support for Psalm (since v2.3.0).

```vim
let g:ale_linters = { 'php': ['php', 'psalm'] }
```

**vim-lsp**

I also got it working with [vim-lsp](https://github.com/prabirshrestha/vim-lsp)

This is the config I used (for Vim):

```vim
au User lsp_setup call lsp#register_server({
     \ 'name': 'psalm-language-server',
     \ 'cmd': {server_info->[expand('vendor/bin/psalm-language-server')]},
     \ 'whitelist': ['php'],
     \ })
```

**coc.nvim**

It also works with [coc.nvim](https://github.com/neoclide/coc.nvim).

Add settings to `coc-settings.json`:

```jsonc
  "languageserver": {
    "psalmls": {
      "command": "vendor/bin/psalm-language-server",
      "filetypes": ["php"],
      "rootPatterns": ["psalm.xml", "psalm.xml.dist"],
      "requireRootPattern": true
    }
  }
```

## VS Code

[Get the Psalm plugin here](https://marketplace.visualstudio.com/items?itemName=getpsalm.psalm-vscode-plugin) (Requires VS Code 1.26+):
