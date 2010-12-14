<?php /*

MediaWiki Aafigure extension

See README for documentation.

Copyright (c) 2010, Mark Lodato <lodatom@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be included
in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

class AafigureSettings {
    public $aafigure, $format, $allowed_formats;
};

$wgAafigureSettings = new AafigureSettings();
$wgAafigureSettings->aafigure = 'aafigure';
$wgAafigureSettings->format = 'png';
$wgAafigureSettings->allowed_formats = array();

$wgExtensionFunctions[] = 'wfAafigureTag';

$wgExtensionCredits['parserhook'][] = array(
    'path' => __FILE__,
    'name' => 'Aafigure',
    'author' => '[mailto:lodatom@gmail.com Mark Lodato]',
    'url' => 'http://www.mediawiki.org/wiki/Extension:Aafigure',
    'description' => 'Parse ASCII Art images in "aafigure".',
    'version' => '1.0'
);

function wfAafigureTag() {
    global $wgParser;
    $wgParser->setHook( 'aafigure', 'renderAafigure' );
}

function renderAafigure( $input, $args, $parser )
{
    global $wgUploadDirectory, $wgUploadPath, $wgAafigureSettings;

    $subdir = '/aafigure/';
    $dir = $wgUploadDirectory . $subdir;

    $format = $wgAafigureSettings->format;
    $options = '';
    $width = 0;
    $height = 0;
    $error = false;
    foreach ($args as $name => $value) {
        switch ($name) {
        case 'format':
            if ( $value != $format ) {
                $value = strtolower( $value );
                $allowed = $wgAafigureSettings->allowed_formats;
                if ( ! ( in_array( $value, $allowed ) ) ) {
                    $error = 'allowed formats: ' . implode(', ', $allowed);
                    break 2;
                }
                $format = $value;
                $options .= ' --type=' . escapeshellarg($format);
            }
            break;
        case 'width':
            $width = intval( $value );
            break;
        case 'height':
            $height = intval( $value );
            break;
        case 'aspect':
        case 'background':
        case 'fill':
        case 'foreground':
        case 'linewidth':
        case 'scale':
            $options .= ' --' . $name . '=' . escapeshellarg($value);
            break;
        case 'proportional':
        case 'textual':
            $options .= ' --' . $name;
            break;
        default:
            $error = 'Unknown option: ' . $name;
            break 2;
        }
    }
    if ( $error ) {
        return '<strong class="error">'.htmlspecialchars($error).'</strong>';
    }

    $extension = $format;

    $hash = md5( 'aafigure' . $options . "\n" . $input );
    $filename = $dir . $hash . '.' . $extension;

    $cmd = $wgAafigureSettings->aafigure . $options . ' -o ' . $filename;

    if ( ! ( file_exists( $filename ) ) )
    {
        if ( ! is_dir( $dir ) ) {
            mkdir( $dir, 0777 );
        }

        $descriptors = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w')
        );
        $pipes = array();
        $process = proc_open( $cmd, $descriptors, $pipes );
        $retval = -1;

        if ( is_resource( $process ) ) {
            fwrite( $pipes[0], $input );
            fclose( $pipes[0] );
            $stdout = stream_get_contents( $pipes[1] );
            $stderr = stream_get_contents( $pipes[2] );
            fclose( $pipes[1] );
            fclose( $pipes[2] );
            $retval = proc_close( $process );
        }

        if ( $retval < 0 ) {
            return '<strong class="error">failed to execute '
                . $wgAafigureSettings->aafigure . '</strong>';
        }
        else if ( $retval > 0 ) {
            return '<strong><pre class="error">aafigure failed with error code '
                . $retval . ":\n" . htmlspecialchars($stderr) . '</pre></strong>';
        }
    }

    $src = $wgUploadPath . $subdir . $hash . '.' . $extension;
    if ( $format == 'svg' ) {
        if ( $width > 0 )
            $width = ' width="' . $width . '"';
        if ( $height > 0 )
            $height = ' height="' . $height . '"';
        return '<object data="' . $src . $width . $height . '"></object>';
    }
    else {
        return '<img src="' . $src . '" />';
    }
}

?>
