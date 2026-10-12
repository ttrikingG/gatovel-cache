# Gatovel Cache

Biblioteca de cache modular e independente para aplicações PHP 8.3+.

O `gatovel/cache` oferece uma interface unificada para armazenamento temporário de dados, com suporte a múltiplos drivers, expiração por TTL e recuperação automática de valores.

O pacote faz parte do ecossistema Gatovel, mas pode ser utilizado independentemente do Gatovel Framework.

## Recursos

- PHP 8.3 ou superior
- Arquitetura modular
- Interface `CacheInterface`
- Driver de memória (`ArrayDriver`)
- Driver de arquivos (`FileDriver`)
- Driver Redis (`RedisDriver`)
- Gerenciamento de drivers com `CacheManager`
- Expiração automática por TTL
- Cache de resultados com `remember()`
- Suporte a valores `null`
- Isolamento de chaves Redis por namespace
- Validação de tipos de dados

## Requisitos

- PHP 8.3+
- Composer
- Redis e `predis/predis` para utilizar o RedisDriver

## Instalação

Após a publicação e disponibilização do pacote no Packagist:

```bash
composer require gatovel/cache
```

Para utilizar o RedisDriver, instale também:

```bash
composer require predis/predis
```

O Redis é opcional. Os drivers Array e File não dependem dele.

## Utilização básica

### ArrayDriver

O ArrayDriver mantém os valores na memória da instância PHP.

```php
<?php

require 'vendor/autoload.php';

use Gatovel\Cache\CacheManager;
use Gatovel\Cache\drivers\ArrayDriver;

$cache = new CacheManager();

$cache->extend('array', new ArrayDriver());

$cache->put('app.name', 'Gatovel');

echo $cache->get('app.name');
```

Os valores armazenados nesse driver não são persistidos entre execuções independentes da aplicação.

### FileDriver

O FileDriver armazena os valores em arquivos no diretório configurado.

```php
<?php

require 'vendor/autoload.php';

use Gatovel\Cache\CacheManager;
use Gatovel\Cache\drivers\FileDriver;

$cache = new CacheManager();

$cache->extend(
    'file',
    new FileDriver(__DIR__ . '/storage/cache')
);

$cache->put('site.title', 'Minha aplicação');

echo $cache->get('site.title');
```

O diretório deve estar disponível para leitura e escrita pelo processo PHP.

### RedisDriver

O RedisDriver utiliza Redis como armazenamento externo.

```php
<?php

require 'vendor/autoload.php';

use Gatovel\Cache\CacheManager;
use Gatovel\Cache\drivers\RedisDriver;
use Predis\Client;

$redis = new Client([
    'scheme' => 'tcp',
    'host' => '127.0.0.1',
    'port' => 6379,
]);

$cache = new CacheManager();

$cache->extend(
    'redis',
    new RedisDriver($redis, 'minha-aplicacao:cache')
);

$cache->put('usuario:1', [
    'id' => 1,
    'nome' => 'Tom',
]);

$usuario = $cache->get('usuario:1');
```

O prefixo do RedisDriver identifica o namespace utilizado pelo cache. O método `flush()` remove as chaves pertencentes a esse namespace.

## Expiração por TTL

O TTL define por quantos segundos um valor permanece válido.

```php
$cache->put('token', 'abc123', 300);
```

Nesse exemplo, o valor expira após 300 segundos.

- `null`: sem expiração automática.
- Inteiro positivo: duração em segundos.
- Zero ou inteiro negativo: remove a chave.

## Recuperar valores

```php
$valor = $cache->get('minha-chave');

$valor = $cache->get('chave-inexistente', 'valor-padrao');
```

## Verificar existência

```php
if ($cache->has('minha-chave')) {
    echo 'Chave encontrada';
}
```

O método `has()` permite diferenciar uma chave inexistente de uma chave que armazena `null`.

## Remover valores

Para remover uma chave:

```php
$cache->forget('minha-chave');
```

Para limpar o cache do driver selecionado:

```php
$cache->flush();
```

A operação `flush()` deve ser utilizada com cuidado, especialmente em ambientes compartilhados.

## Cache automático com remember()

O método `remember()` recupera um valor existente ou executa uma função para produzi-lo.

```php
$produtos = $cache->remember(
    'produtos:destaque',
    600,
    function () {
        return [
            ['id' => 1, 'nome' => 'Notebook'],
            ['id' => 2, 'nome' => 'Mouse'],
        ];
    }
);
```

Na primeira execução, o callback produz o valor, que é armazenado no cache.

Nas execuções seguintes, enquanto a chave permanecer válida, o valor armazenado é reutilizado.

A implementação também permite armazenar e recuperar `null`.

## Trabalhando com múltiplos drivers

```php
use Gatovel\Cache\CacheManager;
use Gatovel\Cache\drivers\ArrayDriver;
use Gatovel\Cache\drivers\FileDriver;

$cache = new CacheManager();

$cache
    ->extend('array', new ArrayDriver())
    ->extend(
        'file',
        new FileDriver(__DIR__ . '/storage/cache')
    );

$cache->setDefaultDriver('file');

$cache->put('config', ['debug' => false]);

$valor = $cache->driver('array')->get('config');
```

O exemplo demonstra que os drivers possuem armazenamentos independentes. A chave gravada no FileDriver não aparece automaticamente no ArrayDriver.

O primeiro driver registrado torna-se o padrão até que outro seja selecionado.

## Tipos de dados suportados

O pacote foi projetado para trabalhar com:

- `string` contendo UTF-8 válido
- `int`
- `float` finito
- `bool`
- `null`
- Arrays compostos por valores suportados

Objetos PHP, recursos, strings com UTF-8 inválido, `INF` e `NAN` não são aceitos para armazenamento.

Os drivers utilizam mecanismos de armazenamento diferentes. Portanto, estruturas complexas devem ser testadas antes de depender de equivalência estrita entre todos os drivers.

## Tratamento de erros

Chaves vazias são rejeitadas.

Valores não suportados geram `InvalidArgumentException`.

Falhas de armazenamento ou dados corrompidos podem gerar exceções específicas da implementação ou da dependência utilizada.

## Arquitetura

```text
src/
├── CacheManager.php
├── contracts/
│   └── CacheInterface.php
├── drivers/
│   ├── ArrayDriver.php
│   ├── FileDriver.php
│   └── RedisDriver.php
└── support/
    └── CacheValueValidator.php
```

O pacote não depende do núcleo do Gatovel Framework.

## Licença

MIT License.

## Repositório

https://github.com/ttrikingG/gatovel-cache
