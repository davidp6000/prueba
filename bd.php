<?php

/**
 * Carga puntero db
 *
 * Devuelve el puntero a la conexión a la BBDD
 *
 * @return [PDO]
 */
function loadBBDD()
{
    /*
     * Devuelve el puntero a la conexión a la BBDD
     */
    try {
        $res = leer_config(dirname(__FILE__) . "/config/configuracion.xml", dirname(__FILE__) . "/config/configuracion.xsd");
        $bd = new PDO($res[0], $res[1], $res[2]);
        return $bd;
    } catch (\Exception $e) {
        echo $e->getMessage();
        exit();
    }
}

/**
 * Lee la configuracion
 *
 * $fichero_config_BBDD es la ruta del fichero con los datos de conexión a la BBDD
 * $esquema es la ruta del fichero XSD para validar la estructura del fichero anterior
 * Si el fichero de configuración existe y es válido, devuelve un array con tres
 * valores: la cadena de conexión, el nombre de usuario y la clave.
 * Si no encuentra el fichero o no es válido, lanza una excepción.
 *
 * @param mixed $fichero_config_BBDD
 * @param mixed $esquema
 *
 * @return [configuraion db]
 */
function leer_config($fichero_config_BBDD, $esquema)
{
    /*
     * $fichero_config_BBDD es la ruta del fichero con los datos de conexión a la BBDD
     * $esquema es la ruta del fichero XSD para validar la estructura del fichero anterior
     * Si el fichero de configuración existe y es válido, devuelve un array con tres
     * valores: la cadena de conexión, el nombre de usuario y la clave.
     * Si no encuentra el fichero o no es válido, lanza una excepción.
     */

    $config = new DOMDocument();
    $config->load($fichero_config_BBDD);
    $res = $config->schemaValidate($esquema);
    if ($res === false) {
        throw new InvalidArgumentException("Revise el fichero de configuración");
    }
    $datos = simplexml_load_file($fichero_config_BBDD);
    $ip = $datos->xpath("//ip");
    $nombre = $datos->xpath("//nombre");
    $usu = $datos->xpath("//usuario");
    $clave = $datos->xpath("//clave");
    $cad = sprintf("mysql:dbname=%s;host=%s", $nombre[0], $ip[0]);
    $resul = [];
    $resul[] = $cad;
    $resul[] = $usu[0];
    $resul[] = $clave[0];
    return $resul;
}

/**
 * Carga la contraseña
 *
 * Recupera la contraseña encriptada de la BBDD cuyo usuario (a través del
 *  parámetro nombre) es la dirección de correo del usuario que va a realizar el pedido
 *
 * @param mixed $nombre
 *
 * @return [Pasword]
 */
function loadPass($nombre)
{
    /*
     *  Recupera la contraseña encriptada de la BBDD cuyo usuario (a través del
     *  parámetro nombre) es la dirección de correo del usuario que va a realizar el pedido
     */
    $bd = loadBBDD();
    $ins = "select clave from restaurantes where correo= '$nombre'";
    $stmt = $bd->query($ins);
    $resul = $stmt->fetch();
    $devol = false;
    if ($resul !== false) {
        $devol = $resul['clave'];
    }
    return $devol;
}

/**
 * @param mixed $nombre
 * @param mixed $clave
 *
 * @return [type]
 */
function comprobar_usuario($nombre, $clave)
{

    /*
     * Comprueba los datos que recibe del formulario del login. Si los datos son correctos
     * devuelve un array con dos campos: codRes (el código del restaurante) y correo
     * con su correo. En caso de error devuelve false
     */
    $devol = false;
    $bd = loadBBDD();
    $hash = loadPass($nombre);
    if (password_verify($clave, $hash)) {
        $ins = "select codRes, correo from restaurantes where correo = '$nombre' ";
        $resul = $bd->query($ins);
        if ($resul->rowCount() === 1) {
            $devol = $resul->fetch();
        }
    }
    return $devol;
}

/**
 * @return [type]
 */
function cargar_categorias()
{
    /*
     * Devuelve un puntero con el código y nombre de las categorías de la BBDD
     * o falso si se produjo un error
     */
    $bd = loadBBDD();
    $ins = "select codCat, nombre from categoria";
    $resul = $bd->query($ins);
    if (!$resul) {
        return false;
    }
    if ($resul->rowCount() === 0) {
        return false;
    }
    //si hay 1 o más
    return $resul;
}

/**
 * @param mixed $codCat
 *
 * @return [type]
 */
function cargar_categoria($codCat)
{
    /*
     * Recibe el código de una categoría y devuelve un array con su nombre y descripción.
     * Si hay algún error en la BBDD o la categoría no existe devuelve FALSE
     */
    $bd = loadBBDD();
    $ins = "select nombre, descripcion from categoria where codcat = $codCat";
    $resul = $bd->query($ins);
    if (!$resul) {
        return false;
    }
    if ($resul->rowCount() === 0) {
        return false;
    }
    //si hay 1 o más
    return $resul->fetch();
}

/**
 * Carga productos
 *
 * Recibe el código de una categoría y devuelve un puntero (PDOStatement) con los
 * productos que tienen stock, incluyendo todas las columnas de la BBDD.
 *
 * @param mixed $codCat
 *
 * @return [resultado productos o false]
 */
function cargar_productos_categoria($codCat)
{
    /*
     * Recibe el código de una categoría y devuelve un puntero (PDOStatement) con los
     * productos que tienen stock, incluyendo todas las columnas de la BBDD.
     */
    $bd = loadBBDD();
    $sql = "select * from productos where codCat  = $codCat AND stock>0";
    $resul = $bd->query($sql);
    if (!$resul) {
        return false;
    }
    if ($resul->rowCount() === 0) {
        return false;
    }
    //si hay 1 o más
    return $resul;
}

/**
 * Cargar categorias
 *
 * Nos devuelve la categoría de un producto indicando su código o FALSE si se
 * ha producido un error.
 *
 * @param mixed $codProd
 *
 * @return [resultado categorias o false]
 */
function cargar_categoria_codProducto($codProd)
{
    /*
     * Nos devuelve la categoría de un producto indicando su código o FALSE si se
     * ha producido un error.
     */
    $bd = loadBBDD();
    $sql = "select CodCat from productos where CodProd  = $codProd";
    $resul = $bd->query($sql);
    if (!$resul) {
        return false;
    }
    if ($resul->rowCount() === 1) {
        return $resul->fetch();
    }
    //si hay 1 o más
    return false;
}

/**
 * Carga los productos
 *
 * Obtiene la información de los productos que se le pasa como parámetro en
 * forma de un array de códigos de productos.
 *
 * @param mixed $codigosProductos
 *
 * @return [Array productos]
 */
function cargar_productos($codigosProductos)
{
    /*
     * Obtiene la información de los productos que se le pasa como parámetro en
     * forma de un array de códigos de productos.
     */
    $bd = loadBBDD();
    //Para crear la lista de procutos como un texto separado por comas.
    $texto_in = implode(",", $codigosProductos);
    $ins = "select * from productos where codProd in($texto_in)";
    $resul = $bd->query($ins);
    if (!$resul) {
        return false;
    }
    return $resul;
}

/**
 * Inserta el pedido
 *
 * Inserta el pedido en la BBDD. Recibe el carrito de la compra y el código del
 * restaurante que realiza el pedido. Si todo va bien, devuelve el código del nuevo
 * pedido. Si hay algún error devuelve FALSE.
 * Para ello hay que:
 * 1. Crear una nueva fila en la tabla pedidos.
 * 2. Crear una fila en la tabla PedidosProductos por cada producto diferente que
 * se pida, usando la clave del nuevo pedido.
 * 3. Hay que actualizar el stock de cada producto por cada producto del pedido.
 *
 * Todas las insercciones tienen que realizarse como una transacción.
 *
 * @param mixed $carrito
 * @param mixed $codRes
 *
 * @return [Cod pedido]
 */
function insertar_pedido($carrito, $codRes)
{
    /*
     * Inserta el pedido en la BBDD. Recibe el carrito de la compra y el código del
     * restaurante que realiza el pedido. Si todo va bien, devuelve el código del nuevo
     * pedido. Si hay algún error devuelve FALSE.
     * Para ello hay que:
     * 1. Crear una nueva fila en la tabla pedidos.
     * 2. Crear una fila en la tabla PedidosProductos por cada producto diferente que
     * se pida, usando la clave del nuevo pedido.
     * 3. Hay que actualizar el stock de cada producto por cada producto del pedido.
     *
     * Todas las insercciones tienen que realizarse como una transacción.
     */
    
    $bd = loadBBDD();
    $bd->beginTransaction();
    $pesototal = 0;
    $hora = date("Y-m-d H:i:s", time());
    // insertar el pedido
    $sql = "insert into pedidos(fecha, enviado, restaurante) 
			values('$hora',0, $codRes)";
    $resul = $bd->query($sql);
    if (!$resul) {
        return false;
    }
    // coger el id del nuevo pedido para las filas detalle
    $pedido = $bd->lastInsertId();
    // insertar las filas en pedidoproductos
    foreach ($carrito as $codProd => $unidades) {
        $sql = "insert into pedidosproductos(CodPed, CodProd, Unidades) 
		             values( $pedido, $codProd, $unidades)";
        $resul = $bd->query($sql);


        $stmt = $bd->query("Select stock from productos where codprod=$codProd");
        list($stock) = $stmt->fetch();
        $sql2 = "UPDATE productos set stock=? where codProd=?";
        $stmt = $bd->prepare($sql2);
        $stock -= $unidades;
        $resultado = $stmt->execute(array($stock, $codProd));



        if (!$resul || !$resultado) {
            $bd->rollback();
            return false;
        }
    }
    $bd->commit();
    return $pedido;  //devuelve el código del nuevo pedido
}
