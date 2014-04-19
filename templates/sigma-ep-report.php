<?php
/**
 * Sigma EP Report Template
 *
 * @package     SigmaEvents
 * @subpackage  SigmaTempaltes
 * @since version 3.5
 */

if( !defined( 'ABSPATH' ) ){
    header('HTTP/1.0 403 Forbidden');
    die('No Direct Access Allowed!');
}
get_header();
echo "<form action='" . get_home_url() . "/post_ep' method='post'>
    <table>
    <tr>
		<td colspan='2'><h1>Formulario de reporte de pagos</h1></td>
    </tr>
    <tr>
		<td>Token (ID)</td><td><input type='text' name='token' value=''> (identifica la operación)</td>
    </tr>
    <tr>
		<td>Monto</td><td><input type='text' name='amount' value=''> (en formato sin coma: '156,40' se debe introducir '15640')</td>
    </tr>
    <tr>
		<td>Estado del pago</td><td><select name='status'>
			<option value='paid'>Pago Acreditado - OK!</option>
			<option value='cancelled'>Rechazado/Cancelado</option>
			<option value='pending'>Pendiente...</option>
		</select></td>
    </tr>
    <tr>
		<td>Motivo</td><td><input type='text' name='reason' value=''></td>
    </tr>
    <tr>
		<td>Código de autorización</td><td><input type='text' name='auth_code' value=''></td>
    </tr>
    <tr>
		<td>Password</td><td><input type='password' name='pwd' value=''></td>
    </tr>
    <tr>
		<td colspan='2'><input type='submit' value='Reportar resultado de la transacción'></td>
    </tr>
    <tr>
        <td colspan='2'>
            <h2> Por favor, luego de reportar una transacción <b>SIEMPRE</b> debe verificar que la misma
            haya sido correctamente actualizada en el sistema, para ello debe ingresar al Tracker online e
            introducir el Token</h2></br>
            <center><h2><a href='http://sigmasecurepay.info/sigma-events/tracker/'>
            http://sigmasecurepay.info/sigma-events/tracker/</a></h2>
            </center></br>
        </td>
    </tr>
    <table>
	</form>";
get_footer();
?>
