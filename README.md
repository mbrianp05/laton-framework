Laton Web Framework
===================

### PHP 8 requerido

### Descargar
`git clone  https://github.com/mbrianp05/Laton.git` luego `composer install`

### Uso
Abre la consola de xampp o cualquiera que tenga acceso al comando php y ejecuta `php --server localhost:90`

### Abre el navegador en localhost:90

##### Los controladores se registran en map.php

Un controlador se crea en `src/controller` y luce asi:

```php
namespace App\Controller;

use Mbrianp\FuncCollection\Logic\AbstractController;
use Mbrianp\FuncCollection\Routing\Routing;
use Mbrianp\FuncCollection\Http\{Response, Request};
use Mbrianp\FuncCollection\Routing\Attribute\Route;

class ControllerName extends AbstractController
{
    #[Route('/', name: 'index')]
    public function method(): Response
    {
        // La vista se buscara en la carpeta templates
        return $this->render('vista.php', ['param1' => 'hola']);
    }
    
    /**
     * Puedes injectar los parametros de la URL al metodo
     * Es obligartorio tiparlo como string y ponerle al parametro 
     * el mismo nombre del parametro de la ruta
     * 
     * Tambien por ejemplo puede injectar un objeto Request
     * para obtener lo enviado por POST o GET, etc 
     * 
     * O un objeto routing para generar rutas
     * 
     * No importa el orden de los parametros, puedes alternarlos como quieras 
     */
    #[Route('/{parametro}', name: 'index_con_parametro')]
    public function metodo2(string $parametro, Request $request, Routing $routing): Response
    {
        // Response here
    }
    
    /**
     * Si se indica una expresion regular dentro de <> en el parametro de la ruta
     * significa que ese parametro debe cumplir con la expresion regular para que la ruta se active
     * si no cumple se dara un error 404
     */
    #[Route('/{nick<@\w+>}', name: 'profile')]
    public function rutaFiltrada(string $nick): Response
    {
    
    }
}
```

En la vista tienes acceso a las variables que pasaste en el controlador

```php
<?php use Mbrianp\FuncCollection\View\ViewHelper; ?>
<html>
    <body>
        <?php echo $param1 ?>
        <!-- Generar rutas -->
        <?php echo ViewHelper::generateUrl('index_con_parametro', ['parametro' => '1']) ?>
    </body>
</html>
```

Los namespaces se corresponden a la carpeta src = App y todo lo demas de mantiene
(src/Entity, en namespace seria: App\Entity)

#### Base de datos (aun no terminado)
Para las bases de datos crea un entidad en src/Entity (el namespace App\Entity)
y crea propiedades publicas y aplicale el atributo Column, solo las propiedades que tengan
el atributo seran interpretadas como columnas en la base de datos.

```php

namespace App\Entity;

use Mbrianp\FuncCollection\ORM\Attributes\Column;
use Mbrianp\FuncCollection\ORM\Attributes\FilledValue;
use Mbrianp\FuncCollection\ORM\Attributes\Id;

class User
{
    #[Id]
    #[Column]    
    public ?int $id = null;

    #[Column]
    public string $name;
    
    #[Column]
    public string $lastname;
    
    #[FilledValue(['name', 'lastname'])]
    #[Column]
    public string $fullname;
    
    #[Column(unique: true)]
    public string $email;
    
    #[Column]
    public string $password;
    
    #[Column(type: 'json')]
    public array $roles;
}
```

Con el atributo column defines la columna como el tipo,
si acepta null, si es unica (como el email), el nombre de la columna, etc.
Si ninguno de estos datos es definido pues entonces son obtenidos de PHP ej:
el nombre de la columna sera el nombre de la propiedad y el tipo sera el tipo de la propiedad.
**El id de cada entidad debe ser definido con el atributo ID y cada entidad debe tener uno y por defecto tiene como valor NULL**

A partir de ese entidad se puede crear la tabla en la base de datos, injectando SchemaGenerator a un metodo del controlador (en el futuro hare para que se pueda hacer desde la consola)

Antes cambia la configuracion de la conexion a la base de datos en .ini (por defecto no tiene base de datos configurada)

```php

namespace App\Controller;

use App\Entity\User;
use Mbrianp\FuncCollection\Http\Response;
use Mbrianp\FuncCollection\ORM\ORM;
use Mbrianp\FuncCollection\ORM\SchemaGenerator;

class Controller
{
    // Aqui se configura la ruta con #[Route]
    public function createDatabase(ORM $orm, SchemaGenerator $schemaGenerator): Response
    {
        $schemaGenerator->createEntityTable(User::class);
        
        // Lo mismo de arriba
        //$orm->getSchemaGenerator()->createEntityTable(User::class);
        
        return new Response('Content');
    }
}
```

La base de datos la puedes crear tambien con SchemaGenerator con createDatabase(nombredelabasededatos), pero bueno ya la debes haber creado manualmente para probar el codigo anterior.

### Insertar datos
Para insertar en la base de datos exiges EntityManager en el controlador.

```php

namespace App\Controller;

use App\Entity\User;

class AppController
{
    public function register(EntityManager $manager): Response
    {
        $user = new User();
        
        $user->name = 'Name';
        $user->lastname = 'Lastname';
        $user->email = 'email@email.email';
        $user->password = 'password';
        
        // Insertar
        $manager->persist($user);
        
        // Response here.
    }
}

```

#### Consultas
Ya por ultimo las consultas a la base de datos se hace con los repositorios
para definir un repositorio a una entidad se le aplica el atributo repository

```php

namespace App\Entity;

use App\Repository\UserRepository;
use Mbrianp\FuncCollection\ORM\Attributes\Repository;

#[Repository(UserRepository::class)]
class User
{
    // El mismo contenido de antes
}
```

La clase UserRepository va en src/Repository y hereda de AbstractRepository para obtener los metodos de consulta basica.

```php

namespace App\Repository;

use App\Entity\User;
use Mbrianp\FuncCollection\ORM\AbstractRepository;

class UserRepository extends AbstractRepository
{
    public static function getRefEntity(): string
    {
        return User::class;
    }
}

```

Lo unico que exige AbstractRepository es que crees un metodo getRefEntity devolviendo la entidad a la que el repositorio esta relacionado.
Para hacer las consultas puedes injectar en el controlador EntityManager y con el metodo getRepository(entityclass) obtienes el repositorio y algunos metodos para hacer consultas. Sin embargo puedes injectar directamente el repositorio tambien (ya de paso explico como se actualiza y se borra un registro).

```php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Mbrianp\FuncCollection\Http\Response;
use Mbrianp\FuncCollection\ORM\EntityManager;
use Mbrianp\FuncCollection\Routing\Attribute\Route;

class AppController
{
    // Aqui va la ruta
    public function index(EntityManager $manager, UserRepository $repository): Response
    {
        // Lo mismo que injectar UserRepository 
        $repository = $manager->getRepository(User::class);
        
        // Metodos
        // Encontrar por el ID
        $repository->find(1);
        
        // Todos los registros de la base de datos
        $repository->findAll();
        
        // Filtrar la busqueda
        $repository->findBy(['country' => 'Cuba']);
        
        // Filtrar la busqueda pero con un solo resultado
        $user = $repository->findOneBy(['country' => 'Cuba']);
        
        // Cambiar de valor la propiedad que se quiere actualizar
        // Todas las demas propiedades tendran de valor el resultado de
        // la base de datos con el metodo findOneBy
        $user->country = 'Italy';
        
        // Va a actualizar dado que el Id esta definido
        // Si el Id no esta definido pues insertara el registro
        // Tambien puedes actualizar un registro sin buscarlo en la base de datos
        // Solamente crear el objeto y asegurar que el ID sea del registro que se va a actualizar
        $manager->persist($user);
        
        // Para eliminar
        $manager->remove($user);
        
        // Response here
    }
    
    /**
     * Otra forma de hacer consultas es exigiendo la entidad como parametro
     * Esto funcionara siempre y cuando en la ruta haya un parametro que coincida con una propiedad (que sea columna) en la entidad
     * Por ej, aqui, en la ruta, hay parametro, id por lo tanto se va a buscar un usuario con el id que se pase en la URL
     *
     * El parametro es obligado tiparlo para asi saber en que tabla buscar
     * y tambien debe aceptar null como valor en case de que no se encuentre ningun resultado 
     */
    #[Route('/profile/{id<\d+>}', name: 'profile')]
    public function profile(?User $user): Response
    {
        return new Response(\var_export($user, true));
    }
}

```

Por ahora eso es todo mas tarde lo separare todo en componentes para instalarlos por composer.