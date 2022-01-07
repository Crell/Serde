# Contributing

Contributions are **welcome** and will be fully **credited**.

We accept contributions via Pull Requests on [Github](https://github.com/Crell/Serde).

## Pull Requests

- **Talk first** - Before filing a Pull Request with a new feature, open an issue to discuss it first.  Not all feature requests are appropriate, and we really hate rejecting a PR after someone has done spec work on it.  Make sure the idea fits with the intent of the library first before trying to file a PR.  (We may be able to suggest a better way of doing it.)

- **[PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)** - Check the code style with ``$ composer check-style`` and fix it with ``$ composer fix-style``.

- **Add tests!** - Your patch won't be accepted if it doesn't have tests.

- **Document any change in behaviour** - Make sure the `README.md` and any other relevant documentation are kept up-to-date.

- **Consider our release cycle** - We try to follow [SemVer v2.0.0](http://semver.org/). Randomly breaking public APIs is not an option.

- **Create feature branches** - Don't ask us to pull from your master branch.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please [squash them](http://www.git-scm.com/book/en/v2/Git-Tools-Rewriting-History#Changing-Multiple-Commit-Messages) before submitting.

- **Be functional** - This library leverages [`Crell/fp`](https://www.github.com/Crell/fp) for easier functional-programming-style code.  Please be consistent and do the same whenever possible.  For instance, don't use a `foreach()` loop when a map or filter would be clearer at communicating intent.

## Running Tests

``` bash
$ composer test
```


**Happy coding**!
