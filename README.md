Laton Web Framework
===================

### PHP 8 requerido

### Para usarlo usa la consola de XAMPP y dirigete a la carpeta del framework y ejecuta "php --server localhost:90"

### Abre el navegador en localhost:90

##### Los controladores se registran en map.php

Un controlador luce:

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
        // La vista se buscara en la carpeta templates de src
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
    #[Route('/{parametro}', name: 'index')]
    public function metodo2(string $parametro, Request $request, Routing $routing): Response
    {
        // La vista se buscara en la carpeta templates de src
        return $this->render('vista.php', ['param1' => 'hola']);
    }
}
```

En la vista tienes acceso a las variables que pasaste en el controlador
```php
<html>
    <body>
        <?php echo $param1; ?>
    </body>
</html>
```

Los namespaces se corresponden a la carpeta src = App y todo lo demas de mantiene
(src/Entity, en namespace seria: App\Entity)

#### Ahora estoy en el tema de las bases de datos pero aun no lo termino.
