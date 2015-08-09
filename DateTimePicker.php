<?php
/**
 * @link http://phe.me
 * @copyright Copyright (c) 2014 Pheme
 * @license MIT http://opensource.org/licenses/MIT
 */

namespace pheme\jui;

use Yii;
use yii\helpers\Json;
use yii\jui\DatePicker;
use yii\jui\JuiAsset;
use yii\helpers\FormatConverter;
use yii\helpers\Html;

/**
 * @author Aris Karageorgos <aris@phe.me>
 */
class DateTimePicker extends DatePicker
{
    /**
     * @var boolean If true, will omit the date portion of the widget.
     */
    public $timeOnly = false;

    /**
     *  see http://trentrichardson.com/examples/timepicker/
     */
    public $timeFormat = '';

    /**
     * Renders the widget.
     */
    public function run()
    {
        $picker = $this->timeOnly ? 'timepicker' : 'datetimepicker';

        echo $this->renderWidget() . "\n";

        $containerID = $this->inline ? $this->containerOptions['id'] : $this->options['id'];
        $language = $this->language ? $this->language : Yii::$app->language;

        if (strncmp($this->dateFormat, 'php:', 4) === 0) {
            $this->clientOptions['dateFormat'] = FormatConverter::convertDatePhpToJui(
                substr($this->dateFormat, 4),
                'date',
                $language
            );
        } else {
            $this->clientOptions['dateFormat'] = FormatConverter::convertDateIcuToJui(
                $this->dateFormat,
                'date',
                $language
            );
        }

        if ($this->timeFormat) {
            $this->clientOptions['timeFormat'] = $this->timeFormat;
        }

        if ($language != 'en-US' && $language != 'en') {
            $view = $this->getView();
            $bundle = DateTimePickerLanguageAsset::register($view);
            if ($bundle->autoGenerate) {
                $fallbackLanguage = substr($language, 0, 2);
                if ($fallbackLanguage !== $language && !file_exists(
                        Yii::getAlias($bundle->sourcePath . "/dist/i18n/jquery-ui-timepicker-$language.js")
                    )
                ) {
                    $language = $fallbackLanguage;
                }
                $view->registerJsFile(
                    $bundle->baseUrl . "/dist/i18n/jquery-ui-timepicker-$language.js",
                    [
                        'depends' => [DateTimePickerAsset::className()],
                    ]
                );
            }
            $options = Json::encode($this->clientOptions);
            $view->registerJs(
                "$('#{$containerID}').{$picker}($.extend({}, $.timepicker.regional['{$language}'], $options));"
            );
        } else {
            $this->registerClientOptions($picker, $containerID);
        }

        $this->registerClientEvents($picker, $containerID);
        DateTimePickerAsset::register($this->getView());
    }

    protected function renderWidget()
    {
        $contents = [];

        // get formatted date value
        if ($this->hasModel()) {
            $value = Html::getAttributeValue($this->model, $this->attribute);
        } else {
            $value = $this->value;
        }

        $options = $this->options;
        $options['value'] = $value;

        if ($this->inline === false) {
            // render a text input
            if ($this->hasModel()) {
                $contents[] = Html::activeTextInput($this->model, $this->attribute, $options);
            } else {
                $contents[] = Html::textInput($this->name, $value, $options);
            }
       } else {
            // render an inline date picker with hidden input
            if ($this->hasModel()) {
                $contents[] = Html::activeHiddenInput($this->model, $this->attribute, $options);
            } else {
                $contents[] = Html::hiddenInput($this->name, $value, $options);
            }
            $this->clientOptions['defaultDate'] = $value;
            $this->clientOptions['altField'] = '#' . $this->options['id'];
            $contents[] = Html::tag('div', null, $this->containerOptions);
        }

        return implode("\n", $contents);
    }
}
