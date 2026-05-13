<?php

/**
 * Tipo de p�gina pensado para pantallas de login, presenta un logo y un pie de p�gina b�sico
 * 
 * @package SalidaGrafica
 */
class tp_gu_kena extends toba_tp_basico
{
	function barra_superior()
	{
		echo "
			<style type='text/css'>
				.cuerpo {
					
				}
			</style>
		";
		echo "<div id='barra-superior' class='barra-superior-login'>\n";
	}

	function pre_contenido()
	{
		echo "<div class='login-titulo'>" . toba_recurso::imagen_proyecto("inicio.png", true);
		echo "<div style='font-weight: bold; font-size:30px;'>2026</div>";

		//        echo "<div>".utf8_decode("versión")." ".toba::proyecto()->get_version()."</div>";
		echo "<div><a style='color:blue;font-size:15px;' href='ord_1097_2025.pdf'>Ver " . utf8_decode('Ordenanza N°1097/25') . "</a></div>";
		echo "<div><a style='color:blue;font-size:15px;' href='Instructivo carga electronica 2026.pdf'>Ver Instructivo</a></div>";
		echo "</div>";
		echo "<div align='center' class='cuerpo'>\n";
	}

	function post_contenido()
	{
		echo "</div>";
		echo "<div class='login-pie'>";
		echo "<div><a href='mailto:gukena@fi.uncoma.edu.ar'>Soporte T&eacute;cnico: gukena@fi.uncoma.edu.ar</a></div>";
		//echo "<div>Desarrollado por <strong><a href='http://www.siu.edu.ar' style='text-decoration: none' target='_blank'>SIU</a></strong></div>
		//    <div>2002-".date('Y')."</div>";
		echo "</div>";
	}
}
