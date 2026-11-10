# Adexos Gotenberg PDF

Adexos Gotenberg is a simple helper that fetches the Gotenberg container configured and exposes some configuration
fields.

# How to install

You can install it by typing : `composer require adexos/m2-gotenberg-pdf:^1.0`

For keep your serveur clean configure the cleanFiles cron in your di.xml after 30 the pdf is remove of the media
subdirectory

```
    <type name="Adexos\Gotenberg\Cron\CleanFiles">
        <arguments>
            <argument name="subMediaDirectories" xsi:type="array">
                <item name="sheet" xsi:type="string">
                    sheets
                </item>
            </argument>
        </arguments>
    </type>
```

You can use the service GotenbergCleanCache for clean file of specific entity for remove caching for exemple in
entityRepository save
