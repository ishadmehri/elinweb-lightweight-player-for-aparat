<?php
namespace LightweightPlayer\Aparat;
defined('ABSPATH') || exit;

class Elementor_Widget extends \Elementor\Widget_Base {
    public function get_name() { return 'lightweight-player-for-aparat'; }
    public function get_title() { return 'پلیر سبک آپارات'; }
    public function get_icon() { return 'eicon-play'; }
    public function get_categories() { return array('general'); }
    public function get_keywords() { return array('aparat', 'video', 'lightweight-player', 'آپارات', 'ویدئو'); }
    public function get_script_depends() { return array('lwpa-player'); }

    protected function register_controls() {
        $this->start_controls_section('video', array('label' => 'ویدئوی آپارات'));
        $this->add_control('aparat_url', array('label' => 'لینک آپارات', 'type' => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'https://www.aparat.com/v/ytf50k5', 'label_block' => true,
            'dynamic' => array('active' => true), 'description' => 'عنوان و پوستر به‌صورت خودکار دریافت و پوستر در وردپرس ذخیره می‌شود.'));
        $this->add_control('video_title', array('label' => 'عنوان دلخواه (اختیاری)', 'type' => \Elementor\Controls_Manager::TEXT,
            'dynamic' => array('active' => true), 'label_block' => true));
        $this->add_control('poster', array('label' => 'پوستر جایگزین (اختیاری)', 'type' => \Elementor\Controls_Manager::MEDIA));
        $this->add_control('ratio', array('label' => 'نسبت تصویر', 'type' => \Elementor\Controls_Manager::SELECT,
            'default' => '16/9', 'options' => array('16/9' => 'افقی 16:9', '9/16' => 'عمودی 9:16', '1/1' => 'مربع 1:1', '4/3' => '4:3')));
        $this->add_control('above_fold', array('label' => 'پوستر در ابتدای صفحه است', 'type' => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes', 'default' => '', 'description' => 'فقط برای ویدئوی قابل مشاهده بدون اسکرول روشن کنید؛ پوستر با اولویت بالا بارگذاری می‌شود.'));
        $this->end_controls_section();
        $this->start_controls_section('playback', array('label' => 'تنظیمات پخش'));
        $this->add_control('player_type', array('label' => 'پلیر ویدئو', 'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'native', 'options' => array('native' => 'پلیر مرورگر؛ پخش تک‌کلیک', 'aparat' => 'پلیر رسمی آپارات'),
            'description' => 'در حالت مرورگر، فایل ویدئو پس از کلیک پخش می‌شود؛ امکانات پیشنهاد ویدئو مخصوص پلیر رسمی‌اند.'));
        $this->add_control('start_time', array('label' => 'شروع پخش از (ثانیه)', 'type' => \Elementor\Controls_Manager::NUMBER,
            'min' => 0, 'max' => 2147483647, 'step' => 1, 'description' => 'مثلاً ۶۵ یعنی ۱ دقیقه و ۵ ثانیه. خالی: زمان داخل لینک یا شروع ویدئو.'));
        $this->add_control('muted', array('label' => 'پخش اولیه بی‌صدا', 'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'inherit', 'options' => array('inherit' => 'از لینک / پیش‌فرض آپارات', 'true' => 'بله', 'false' => 'خیر')));
        $this->add_control('title_show', array('label' => 'نمایش عنوان و آیکون‌های پلیر', 'type' => \Elementor\Controls_Manager::SELECT,
            'condition' => array('player_type' => 'aparat'),
            'default' => 'inherit', 'options' => array('inherit' => 'از لینک / پیش‌فرض آپارات', 'true' => 'نمایش', 'false' => 'پنهان')));
        $this->add_control('recommendations', array('label' => 'پیشنهادهای پایان ویدئو', 'type' => \Elementor\Controls_Manager::SELECT,
            'condition' => array('player_type' => 'aparat'),
            'default' => 'inherit', 'options' => array('inherit' => 'از لینک / پیش‌فرض آپارات', 'default' => 'پیش‌فرض آپارات', 'self' => 'فقط ویدئوهای همین کانال')));
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $hash = Parser::hash($settings['aparat_url'] ?? '');
        $editing = \Elementor\Plugin::$instance->editor->is_edit_mode();
        if ($editing && $hash && current_user_can('upload_files') && current_user_can('edit_posts')) {
            $result = Metadata::resolve($hash);
            if (is_wp_error($result)) {
                echo '<p>' . esc_html($result->get_error_message()) . '</p>';
            }
        }
        if (!$hash && $editing) {
            echo '<p>لینک معتبر آپارات را وارد کنید.</p>';
            return;
        }
        $args = array('url' => $settings['aparat_url'] ?? '', 'title' => $settings['video_title'] ?? '',
            'posterId' => $settings['poster']['id'] ?? 0, 'ratio' => $settings['ratio'] ?? '16/9',
            'aboveFold' => ($settings['above_fold'] ?? '') === 'yes', 'playerType' => $settings['player_type'] ?? 'native');
        foreach (array('start_time' => 'startTime', 'muted' => 'muted', 'title_show' => 'titleShow', 'recommendations' => 'recom') as $control => $key) {
            if (isset($settings[$control]) && $settings[$control] !== '' && $settings[$control] !== 'inherit') {
                $args[$key] = $settings[$control];
            }
        }
        echo Renderer::render($args);
    }
}

/** Hidden compatibility widget for existing Elementor documents. */
final class Legacy_Elementor_Widget extends Elementor_Widget {
    public function get_name() { return 'dadsoo-aparat-performance'; }
    public function show_in_panel() { return false; }
}
