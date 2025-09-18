# Info

Symfony issue : https://github.com/symfony/symfony/issues/61679

## Run project locally

 - Install dependencies : `composer install`
 - Run server : `symfony server:start -d`
 - Access to your localhost and follow links to tests


### Try PR https://github.com/symfony/symfony/pull/59576

- Clone symfony/symfony repository
- Follow this doc to use PR code : https://symfony.com/doc/current/contributing/community/reviews.html#the-pull-request-review-process
```bash
git fetch origin pull/59576/head:pr59576
git checkout pr59576
```
- Run this project with PR code : https://symfony.com/doc/current/contributing/code/pull_requests.html#use-your-branch-in-an-existing-project
```bash
php link /path/to/your/project
```
- Access to your localhost and follow links to tests
