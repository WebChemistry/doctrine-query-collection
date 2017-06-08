Doctrine query collection
=========================

Vytvoření kolekce (stačí jedna v aplikaci):

```php
$queryCollection = new QueryCollection($entityManager);
```

Vytvoření dotazu v doctrině
```php
$query = $entityManager->getRepository('Entity')->createQueryBuilder('e')->where('e.name = :name')
	->setParameter('name', $name)->getQuery();
```

Přidání dotazu do kolekce a výsledek uložit do proměnné, uloží se Generátor, který se pozastaví před získáním
výsledků z dotazu, ale stihne před tím posbírat všechny data o dotazu, aby jej vykonal v nejvhodnější dobu.
```php
$result = $queryCollection->fromQuery($query);
$result2 = $queryCollection->fromQuery($query);
```

Nyní rozdá do proměnných $result a $result2 potřebné výsledky po vykonaní jediného dotazu.
```php
foreach ($result as $row) {

}
```

Bez doctrine query collection:
![bez](https://pichoster.net/images/2017/06/08/d5be600745c9038ab7cdb34f8fd18748.png)

s doctrine query collection:
![s](https://pichoster.net/images/2017/06/08/93d473ca11aa5f740d03ef969e72f97d.png)
