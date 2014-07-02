<?php

/**
 * @package     FOM
 * @subpackage  Notificaciones 
 * @author      Pablo Andr�s V�lez Vidal 
 * @license     http://www.gnu.org/licenses/gpl.txt
 * @copyright   Copyright (c) 2012 Genesys Soft.
 * @version     0.2
 * 
 * Clase encargada de gestionar la informaci�n del listado de notificaciones existentes en el sistema para un usuario determinado.
 * Esta clase permitir� agregar notificaciones o eventos a una determinada fecha, as� mismo permitir� la modificaci�n de dichas notificaciones
 * y generar� alertas si estas notificaciones o eventos tienen recordatorios. Estas notificaciones ser�n usadas como un modulo independiente 
 * (como una especie de ajenda) y tambi�n funcionar� haciendo uso de las notidficaciones que el sistema le genere a un usuario determinado, por
 * ejemplo el pago de alguna factura a un proveedor, el cobro a un cliente, o el vencimiento de la resoluci�n de facturaci�n vigente.
 * */
class Notificacion {

    /**
     * C�digo interno o identificador del item en la base de datos
     * @var entero
     */
    public $id;

    /**
     * URL relativa del m�dulo 
     * @var cadena
     */
    public $urlBase;

    /**
     * URL relativa del m�dulo 
     * @var cadena
     */
    public $url;

    /**
     * C�digo interno o identificador del usuario al que pertenece esta notificaci�n
     * @var entero
     */
    public $idUsuario;

    /**
     * objeto usuario al que pertenece la notificacion
     * @var entero
     */
    public $usuario;

    /**
     * titulo del item
     * @var entero
     */
    public $titulo;
    
    /**
     * Texto descriptivo de la notificacion
     * @var entero
     */
    public $descripcion;
    
    /**
     * fecha de la notificacion
     * @var entero
     */
    public $fecha;
    
    /**
     * fecha de alerta de la notificaci�n
     * @var entero
     */
    public $fechaAlerta;  
    
    /**
     * Tipo de la notificaci�n 1=evento, 2=recordatorio, 3=alerta
     * @var entero
     */
    public $tipoNotificacion;    

    /**
     * Determina si esta notificaci�n se encuentra o no activa
     * @var entero
     */
    public $activo;

    /**
     * Indicador del orden cronol�gio de la lista de registros
     * @var l�gico
     */
    public $listaAscendente = TRUE;

    /**
     * N�mero de registros de la lista
     * @var entero
     */
    public $registros = NULL;

    /**
     * N�mero de registros activos de la lista de cajas
     * @var entero
     */
    public $registrosActivos = NULL;

    /**
     * N�mero de registros activos de la lista de cajas
     * @var entero
     */
    public $registrosConsulta = NULL;

    /**
     * Orden predeterminado para organizar los listados
     * @var entero
     */
    public $ordenInicial = NULL;

    /**
     * Inicializar una caja
     * @param entero $id C�digo interno o identificador del caja en la base de datos
     */
    public function __construct($id = NULL) {
        global $sql, $modulo;
        $this->urlBase = '/' . $modulo->url;
        $this->url = $modulo->url;
        //establecer el valor del campo predeterminado para organizar los listados
        $this->ordenInicial = 'c.nombre';

        if (isset($id)) {
            $this->cargar($id);
        }
    }

    /**
     * Cargar los datos de un item
     * @param entero $id C�digo interno o identificador del item en la base de datos
     */
    public function cargar($id) {
        global $sql;

        if (isset($id) && $sql->existeItem('notificaciones', 'id', intval($id))) {

            $tablas = array(
                'n' => 'notificaciones'
            );

            $columnas = array(
                'id' => 'c.id',
                'nombre' => 'c.nombre',
                'idSede' => 'c.id_sede',
                'sede' => 's.nombre',
                'activo' => 'c.activo'
            );

            $condicion = 'c.id_sede = s.id  AND c.id = "' . $id . '"';

            $consulta = $sql->seleccionar($tablas, $columnas, $condicion);

            if ($sql->filasDevueltas) {
                $fila = $sql->filaEnObjeto($consulta);

                foreach ($fila as $propiedad => $valor) {
                    $this->$propiedad = $valor;
                }
            }
        }
    }

    /**
     * Adicionar una caja
     * @param  arreglo $datos       Datos de la caja a adicionar
     * @return entero               C�digo interno o identificador del caja en la base de datos (NULL si hubo error)
     */
    public function adicionar($datos) {
        global $sql;

        $datosItem = array(
            'id_sede' => $datos['id_sede'],
            'nombre' => $datos['nombre'],
            'activo' => ( isset($datos['activo']) ) ? '1' : '0'
        );

        $consulta = $sql->insertar('cajas', $datosItem);

        if ($consulta) {
            $idItem = $sql->ultimoId;
            return $idItem;
        } else {
            return NULL;
        }
    }

    /**
     * Modificar una caja
     * @param  arreglo $datos       Datos del caja a modificar
     * @return l�gico               Indica si el procedimiento se pudo realizar correctamente o no
     */
    public function modificar($datos) {
        global $sql;

        if (!isset($this->id)) {
            return NULL;
        }

        $datosItem = array(
            'id_sede' => $datos['id_sede'],
            'nombre' => $datos['nombre'],
            'activo' => ( isset($datos['activo']) ) ? '1' : '0'
        );

        $consulta = $sql->modificar('cajas', $datosItem, 'id = "' . $this->id . '"');


        if ($consulta) {
            return 1;
        } else {
            return NULL;
        }
    }

    /**
     * Eliminar una caja
     * @param entero $id    C�digo interno o identificador de una caja en la base de datos
     * @return l�gico       Indica si el procedimiento se pudo realizar correctamente o no
     */
    public function eliminar() {
        global $sql;

        if (!isset($this->id)) {
            return NULL;
        }

        if (($consulta = $sql->eliminar('cajas', 'id = "' . $this->id . '"'))) {

            return true;
        } else {
            return false;
        }//fin del si funciono eliminar
    }

    /**
     * Listar las cajas
     * @param entero  $cantidad    N�mero de cajas a incluir en la lista (0 = todas las entradas)
     * @param arreglo $excepcion   Arreglo con los c�digos internos o identificadores a omitir en la lista
     * @param cadena  $condicion   Condici�n adicional (SQL)
     * @return arreglo             Lista de cajas
     */
    public function listar($inicio = 0, $cantidad = 0, $excepcion = NULL, $condicionGlobal = NULL, $orden = NULL) {
        global $sql, $textos;

        /*         * * Validar la fila inicial de la consulta ** */
        if (!is_int($inicio) || $inicio < 0) {
            $inicio = 0;
        }

        /*         * * Validar la cantidad de registros requeridos en la consulta ** */
        if (!is_int($cantidad) || $cantidad <= 0) {
            $cantidad = 0;
        }

        /*         * * Validar que la condici�n sea una cadena de texto ** */
        if (!is_string($condicionGlobal)) {
            $condicion = '';
        }

        /*         * * Validar que la excepci�n sea un arreglo y contenga elementos ** */
        if (isset($excepcion) && is_array($excepcion)) {
            $excepcion = implode(',', $excepcion);
            $condicion .= 'c.id NOT IN (' . $excepcion . ') AND ';
        }

        /*         * * Definir el orden de presentaci�n de los datos ** */
        if (!isset($orden)) {
            $orden = $this->ordenInicial;
        }
        if ($this->listaAscendente) {
            $orden = $orden . ' ASC';
        } else {
            $orden = $orden . ' DESC';
        }


        $tablas = array(
            'c' => 'cajas',
            's' => 'sedes_empresa'
        );

        $columnas = array(
            'id' => 'c.id',
            'nombre' => 'c.nombre',
            'sede' => 's.nombre',
            'activo' => 'c.activo'
        );

        if (!empty($condicionGlobal)) {
            $condicion .= $condicionGlobal . ' AND ';
        }

        $condicion .= ' c.id_sede = s.id';



        if (is_null($this->registrosConsulta)) {
            $sql->seleccionar($tablas, $columnas, $condicion);
            $this->registrosConsulta = $sql->filasDevueltas;
        }
        $consulta = $sql->seleccionar($tablas, $columnas, $condicion, 'c.id', $orden, $inicio, $cantidad);
        if ($sql->filasDevueltas) {
            $lista = array();

            while ($objeto = $sql->filaEnObjeto($consulta)) {

                $objeto->estado = ($objeto->activo) ? HTML::frase($textos->id('ACTIVO'), 'activo') : HTML::frase($textos->id('INACTIVO'), 'inactivo');

                $lista[] = $objeto;
            }
        }

        return $lista;
    }

    /**
     * Metodo encargado de generar la tabla con el listado de registros de las bodegas
     * @global type $textos
     * @param type $arregloRegistros
     * @param type $datosPaginacion
     * @return type
     */
    public function generarTabla($arregloRegistros, $datosPaginacion = NULL) {
        global $textos;
        //Declaracion de las columnas que se van a mostrar en la tabla
        $datosTabla = array(
            HTML::parrafo($textos->id('SEDE'), 'centrado') => 'sede|s.nombre',
            HTML::parrafo($textos->id('NOMBRE'), 'centrado') => 'nombre|c.nombre',
            HTML::parrafo($textos->id('ESTADO'), 'centrado') => 'estado'
        );
        //ruta a donde se mandara la accion del doble click
        $ruta = '/ajax' . $this->urlBase . '/move';


        return Recursos::generarTablaRegistros($arregloRegistros, $datosTabla, $ruta, $datosPaginacion) . HTML::crearMenuBotonDerecho('CAJAS');
    }

}