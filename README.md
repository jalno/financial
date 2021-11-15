# HOWTO Install
By using Composer!   
Because this project is private and host on Gitlab private package repository you must some extra steps to install this on your project.

First add repository to your composer.json
```
composer config repositories.abedi/financial '{"type": "composer", "url": "https://composer.jeyserver.com/abedi/financial/packages.json"}'
```

Add Jeyserver's Composer repository and Gitlab into your composer.json
```
composer config gitlab-domains composer.jeyserver.com git.jeyserver.com
```

Make a [Personal Access Token](https://git.jeyserver.com/-/profile/personal_access_tokens) in Gitlab with minimum `read_api` permission and create an auth.json file with that:
```
composer config [-g] gitlab-token.git.jeyserver.com <personal_access_token>
composer config [-g] gitlab-token.composer.jeyserver.com <personal_access_token>
```

And as final step set the required package version:
```
composer require abedi/financial:<version>
```