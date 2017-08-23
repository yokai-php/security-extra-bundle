YokaiSecurityExtraBundle
========================

todo badges


Installation
------------

### Add the bundle as dependency with Composer

``` bash
$ composer require yokai/security-extra-bundle:1.0-dev
```

### Enable the bundle in the kernel

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = [
        // ...
        new Yokai\SecurityExtraBundle\YokaiSecurityExtraBundle(),
    ];
}
```

### Configuration

Imagine that you handle an entity named `AppBundle\Entity\Article` on which you has basic CRUD operations.

You can imagine what kind of checks you will have to implements to secure your CRUD actions :

- browsing posts is allowed if you have the role `ROLE_ARTICLE_LIST`
- displaying post details is allowed if you have the role `ROLE_ARTICLE_SHOW`
- creating a new post is allowed if you have the role `ROLE_ARTICLE_CREATE`
- updating an existing post is allowed if you have the role `ROLE_ARTICLE_UPDATE` 
  **AND** if you created this post in the first place
- deleting an existing post is allowed if you have the role `ROLE_ARTICLE_DELETE` 
  **AND** if you created this post in the first place

OK, here is a way to configure it :

``` yaml
# app/config/config.yml

yokai_security_extra:
    permissions:

        - attributes: 'browse'
          roles:      ROLE_ARTICLE_LIST

        - attributes: 'details'
          subjects:   AppBundle\Entity\Article
          roles:      ROLE_ARTICLE_SHOW

        - attributes: 'create'
          roles:      ROLE_ARTICLE_CREATE

        - attributes: 'update'
          subjects:   AppBundle\Entity\Article
          roles:      ROLE_ARTICLE_UPDATE
          callables:  'isOwnedBy'

        - attributes: 'delete'
          subjects:   AppBundle\Entity\Article
          roles:      ROLE_ARTICLE_DELETE
          callables:  'isOwnedBy'
```

**note** `isOwnedBy` is a method available on `AppBundle\Entity\Article` that could look like
```php
public function isOwnedBy(User $user)
{
    return $this->author === $user;
}
```

### Advanced

todo


MIT License
-----------

License can be found [here](https://github.com/yokai-php/security-extra-bundle/blob/master/LICENSE).


Authors
-------

The bundle was originally created by [Yann Eugoné](https://github.com/yann-eugone).

See the list of [contributors](https://github.com/yokai-php/security-extra-bundle/contributors).
