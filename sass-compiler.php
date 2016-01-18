<?php

/**
 * Class SassCompiler
 *
 * This simple tool compiles all .scss files in folder A to .css files (with exactly the same name) into folder B.
 * Everything happens right when you run your app, on-the-fly, in pure PHP. No Ruby needed, no configuration needed.
 *
 * SassWatcher is not a standalone compiler, it's just a little method that uses the excellent scssphp compiler written
 * by Leaf Corcoran (https://twitter.com/moonscript), which can be found here: http://leafo.net/scssphp/ and adds
 * automatic compiling to it.
 *
 * The currently supported version of SCSS syntax is 3.2.12, which is the latest one.
 * To avoid confusion: SASS is the name of the language itself, and also the "name" of the "first" version of the
 * syntax (which was quite different than CSS). Then SASS's syntax was changed to "SCSS", which is more like CSS, but
 * with awesome additional possibilities and features.
 *
 * The compiler uses the SCSS syntax, which is recommended and mostly used. The old SASS syntax is not supported.
 *
 * @see SASS Wikipedia: http://en.wikipedia.org/wiki/Sass_%28stylesheet_language%29
 * @see SASS Homepage: http://sass-lang.com/
 * @see scssphp, the used compiler (in PHP): http://leafo.net/scssphp/
 */
class SassCompiler
{
  /**
   * Compiles all .scss files in a given folder into .css files in a given folder
   *
   * @param string $scss_folder source folder where you have your .scss files
   * @param string $css_folder destination folder where you want your .css files
   * @param string $format_style CSS output format, see http://leafo.net/scssphp/docs/#output_formatting for more.
   */
  static public function run($scss_folder, $css_folder, $format_style = "scss_formatter")
  {
    // scssc will be loaded automatically via Composer
    $scss_compiler = new scssc();
    // set the path where your _mixins are
    $scss_compiler->setImportPaths($scss_folder);
    // set css formatting (normal, nested or minimized), @see http://leafo.net/scssphp/docs/#output_formatting
    $scss_compiler->setFormatter($format_style);

    // create master css file
    if(!is_dir($css_folder)) {
      file_put_contents($css_folder, "");
    }

    if ($handle = opendir($scss_folder)) {
	    while (false !== ($file = readdir($handle))) {
		    if(in_array($file, array(".", ".."))) continue;

		    if(is_dir($scss_folder.$file)) {
			    if(!file_exists($css_folder.$file)) {
				    if(is_dir($css_folder)) {
					    if(!mkdir($css_folder.$file."/")) {
						    throw new Exception("Falha ao compilar arquivo .scss. Não foi possível criar a pasta {$css_folder}{$file}/");
					    }

					    if(!chmod($css_folder.$file, 0755)) {
						    throw new Exception("Falha ao compilar arquivo .scss. Não foi possível mudar a permissão do arquivo {$css_folder}{$file}/ para 755 (rwxr-xr-x)");
					    }
				    }
			    }

			    if(is_dir($css_folder)) {
				    SassCompiler::run($scss_folder.$file."/", $css_folder.$file."/", $format_style);
			    } else {
				    SassCompiler::run($scss_folder.$file."/", $css_folder, $format_style);
			    }
		    } else {
			    if(preg_match('/(.*).scss$/', $file) and $file[0]!='.') {
				    // get path elements from that file
				    $file_path_elements = pathinfo($file);
				    // get file's name without extension
				    $file_name = $file_path_elements['filename'];
				    // get .scss's content, put it into $string_sass
				    $string_sass = file_get_contents($scss_folder . $file_name . ".scss");
				    // compile this SASS code to CSS
				    $string_css = $scss_compiler->compile($string_sass);

				    // write CSS into file with the same filename, but .css extension
				    if(is_dir($css_folder)) {
					    file_put_contents($css_folder . $file_name . ".css", $string_css);
				    } else {
					    file_put_contents($css_folder, $string_css, FILE_APPEND);
				    }
			    }
		    }
	    }
    }
  }
}
