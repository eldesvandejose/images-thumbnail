<?php
	class reescalado{
		// Definimos las propiedades necesarias.
		public $nombreArchivoOriginal = "";	// El nombre del archivo original y su ruta, 
							// con respecto al script llamante, no a este, en su caso.
		private $nombreArchivoReescalado = "";	// El nombre del archivo reeacalado y su ruta, 
							// con respecto al script llamante, no a este, en su caso.
		private $manejadorOriginal;		// El manejador para el archivo original.
		private $manejadorReescalado;		// El manejador para el archivo reescalado.
		private $anchuraOriginal;		// La anchura original en píxeles.
		private $alturaOriginal;		// La altura original en píxeles.
		private $pesoOriginal;			// El peso de la imagen original en bytes.
		private $anchuraDeReescalado;		// La anchura que queda a la imagen tras reescalar.
		private $anchuraMaximaDeReescalado;	// La máxima anchura que podrá tener la imagen tras reescalar.
		private $alturaDeReescalado;		// La altura que queda a la imagen tras reescalar.
		private $alturaMaximaDeReescalado;	// La máxima altura que podrá tener la imagen tras reescalar.
		private $pesoDeReescalado;		// El peso que queda a la imagen tras reescalar.
		private $orientacion;			// Si la imagen es horizontal ("H"), vertical ("V") o neutra ("N");
		
		public static $prefijoDeReescalados = "RESIZED_";	// El prefijo que se añadirá al nombre del archivo de la 
									// imagen reescalada, si es el caso.
		private static $prefijoDeTemporales = "tmp_";		// El prefijo que se añadirá al nombre del archivo de la 
									// imagen reescalada temporal, si es el caso.
		public static $rutaDeOriginales = "";			// La ruta donde se encuentra el archivo original, con respecto al
									// script llamante, no a este.
									// Si se especifica, debe terminar con "/".
									// Si no se especifica, se asumirá que está en la misma que el 
									// script llamante.
		public static $rutaDeReescalados = "";			// La ruta donde se grabarán los reescalados, con respecto al
									// script llamante, no a este.
									// Si se especifica, debe terminar con "/".
									// Si no se especifica ruta, se grabarán en la ruta de origen 
									// con el prefijo_de_reescalados. Si este se especifica como "" 
									// (cadena vacía), se restaurará a "RESIZED_".
									// El objetivo es no "machacar" la imagen original.
		public static $anchuraMaxDeReescalados = 150; 		// En píxeles
		public static $alturaMaxDeReescalados = 100; 		// En píxeles
		public static $pesoMaxDeReescalados = 50000;
		
		/* EL CONSTRUCTOR */
		public function reescalado($nombreDeArchivoOriginal){
			$this->nombreArchivoOriginal = reescalado::$rutaDeOriginales.$nombreDeArchivoOriginal;
			/* Se determina la anchura y altura de la imagen original. */
			$this->anchuraOriginal = getimagesize($this->nombreArchivoOriginal)[0];
			$this->alturaOriginal = getimagesize($this->nombreArchivoOriginal)[1];
			/* Se determina si la imagen es vartical, apaisada o neutra. */
			$this->orientacion = ($this->anchuraOriginal > $this->alturaOriginal)?"H":(($this->anchuraOriginal == $this->alturaOriginal)?"N":"V");
			/* En base a la orientación de la imagen, se determina su anchura y altura máximas, 
			según lo indicado en las propiedades de la clase. */
			switch($this->orientacion){
				case "H":
					$this->anchuraMaximaDeReescalado = reescalado::$anchuraMaxDeReescalados;
					$this->alturaMaximaDeReescalado = reescalado::$alturaMaxDeReescalados;
					break;
				case "N":
					$this->anchuraMaximaDeReescalado = reescalado::$anchuraMaxDeReescalados;
					$this->alturaMaximaDeReescalado = reescalado::$anchuraMaxDeReescalados;
					break;
				case "V":
					$this->anchuraMaximaDeReescalado = reescalado::$alturaMaxDeReescalados;
					$this->alturaMaximaDeReescalado = reescalado::$anchuraMaxDeReescalados;
					break;
			}
			/* Se determina el nombre (con la ruta, si procede) de la imagen reescalada. */
			if (reescalado::$rutaDeReescalados == "" || reescalado::$rutaDeReescalados == reescalado::$rutaDeOriginales) reescalado::$rutaDeReescalados = reescalado::$rutaDeOriginales;
			if (reescalado::$rutaDeReescalados == reescalado::$rutaDeOriginales && reescalado::$prefijoDeReescalados == "")	reescalado::$prefijoDeReescalados = "RESIZED_";
			$this->nombreArchivoReescalado = reescalado::$rutaDeReescalados.reescalado::$prefijoDeReescalados.$nombreDeArchivoOriginal;
 
			/* Si la anchura y/o la altura exceden los máximos, se calcula la anchura y altura definitivas. */
			if ($this->anchuraOriginal > $this->anchuraMaximaDeReescalado || $this->alturaOriginal > $this->alturaMaximaDeReescalado){
				$coeficienteHorizontal = $this->anchuraOriginal / $this->anchuraMaximaDeReescalado;
				$coeficienteVertical = $this->alturaOriginal / $this->alturaMaximaDeReescalado;
				$coeficienteDeReescalado = max($coeficienteHorizontal, $coeficienteVertical);
			} else {
				$coeficienteDeReescalado = 1;
			}
			$this->anchuraDeReescalado = round($this->anchuraOriginal / $coeficienteDeReescalado);
			$this->alturaDeReescalado = round($this->alturaOriginal / $coeficienteDeReescalado);
 
			/* Ya tenemos las dimensiones finales. Ahora hay que grabar una imagen temporal con dichas dimensiones. 
			A partir de ahi, se empezará la comprobación del peso. */
			
			/* Se crea el manejador para una imagen sin contenido de las dimensiones temporales. */
			$this->manejadorReescalado = imagecreatetruecolor($this->anchuraDeReescalado, $this->alturaDeReescalado);
			/* Segun el tipo de imagen, se crea un manejador a partir de la imagen original. */
			switch(getimagesize($this->nombreArchivoOriginal)[2]){ // El tipo de imagen
				case "1": // gif
					$this->manejadorOriginal = imagecreatefromgif($this->nombreArchivoOriginal);
					break;
				case "2": // jpg
					$this->manejadorOriginal = imagecreatefromjpeg($this->nombreArchivoOriginal);
					break;
				case "3": // png
					$this->manejadorOriginal = imagecreatefrompng($this->nombreArchivoOriginal);
					break;
			}
			// Se copia la imagen original en la escalada, a través de sus manejadores.
			imagecopyresized($this->manejadorReescalado, $this->manejadorOriginal, 0, 0, 0, 0, $this->anchuraDeReescalado, $this->alturaDeReescalado, $this->anchuraOriginal, $this->alturaOriginal);
			// Se graba la imagen del manejador de reescalado en un archivo.
			$archivoTemporal = reescalado::$rutaDeReescalados.reescalado::$prefijoDeTemporales.$nombreDeArchivoOriginal;
			switch(getimagesize($this->nombreArchivoOriginal)[2]){ // El tipo de imagen
				case "1": // gif
					imagegif ($this->manejadorReescalado, $archivoTemporal);
					break;
				case "2": // jpg
					imagejpeg ($this->manejadorReescalado, $archivoTemporal);
					break;
				case "3": // png
					imagepng ($this->manejadorReescalado, $archivoTemporal);
					break;
			}
			
			/* Ahora se comprueba si el peso del archivo excede del límite. */
			if (filesize($archivoTemporal) > reescalado::$pesoMaxDeReescalados) {
				$coeficiente = filesize($archivoTemporal) / reescalado::$pesoMaxDeReescalados;
				$anchuraDeTemporal = getimagesize($archivoTemporal)[0];
				$alturaDeTemporal = getimagesize($archivoTemporal)[1];
				$this->anchuraDeReescalado = round($anchuraDeTemporal / $coeficienteDeReescalado);
				$this->alturaDeReescalado = round($alturaDeTemporal / $coeficienteDeReescalado);
				/* Se crea el manejador para una imagen sin contenido de las dimensiones temporales. */
				$this->manejadorReescalado = imagecreatetruecolor($this->anchuraDeReescalado, $this->alturaDeReescalado);
				/* Segun el tipo de imagen, se crea un manejador a partir de la imagen original. */
				switch(getimagesize($this->nombreArchivoOriginal)[2]){ // El tipo de imagen
					case "1": // gif
						$this->manejadorOriginal = imagecreatefromgif($archivoTemporal);
						break;
					case "2": // jpg
						$this->manejadorOriginal = imagecreatefromjpeg($archivoTemporal);
						break;
					case "3": // png
						$this->manejadorOriginal = imagecreatefrompng($archivoTemporal);
						break;
				}
				// Se copia la imagen temporal en la escalada, a través de sus manejadores.
				imagecopyresized($this->manejadorReescalado, $this->manejadorOriginal, 0, 0, 0, 0, $this->anchuraDeReescalado, $this->alturaDeReescalado, $anchuraDeTemporal, $alturaDeTemporal);
				// Se graba la imagen del manejador de reescalado en un archivo definitivo.
				$archivoFinal = reescalado::$rutaDeReescalados.reescalado::$prefijoDeReescalados.$nombreDeArchivoOriginal;
				switch(getimagesize($this->nombreArchivoOriginal)[2]){ // El tipo de imagen
					case "1": // gif
						imagegif ($this->manejadorReescalado, $archivoFinal);
						break;
					case "2": // jpg
						imagejpeg ($this->manejadorReescalado, $archivoFinal);
						break;
					case "3": // png
						imagepng ($this->manejadorReescalado, $archivoFinal);
						break;
				}
				/* Se elimina el archivo temporal */
				unlink($archivoTemporal);
			} else {
				$archivoDefinitivo = reescalado::$rutaDeReescalados.reescalado::$prefijoDeReescalados.$nombreDeArchivoOriginal;
				rename ($archivoTemporal, $archivoDefinitivo);
			}
		}
	}
?>