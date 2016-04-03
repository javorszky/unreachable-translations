# See unreachable translations

When you try to use translating functions in WordPress before loading your plugin's textdomain, it doesn't work.

This plugin will help you identify where they are coming from.

## Usage

1. You need to edit the file. Adding a nice UI to set and sanitize the setting is about 4 times more code than what's already here, I'm lazy, and it's aimed at developers, so you can do it.
2. Activate it.
3. Look at the database table that was generated. It doesn't list repeat calls to the same thing more than once, so you should end up only with the unique calls.
4. The backtrace has, well... the entire backtrace available as serialised array, in case you want to figure out where the translation function was called from.

## Need to say this

Because it's hooking into a lot of stuff, it will make your site slower. Do not use on live site, seriously. You have been warned.
