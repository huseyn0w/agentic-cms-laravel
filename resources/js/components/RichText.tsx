import { Editor } from '@tinymce/tinymce-react';

// Self-hosted TinyMCE v4 (tinymce@^4.9.11) — no CDN. All paths below were
// verified against node_modules/tinymce/ (v4.9.11 dist layout). Each
// 'tinymce/themes/modern' and 'tinymce/plugins/<name>' import resolves via
// that folder's index.js, which requires the sibling theme.js/plugin.js.
import 'tinymce/tinymce';
import 'tinymce/themes/modern';
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/image';
import 'tinymce/plugins/charmap';
import 'tinymce/plugins/print';
import 'tinymce/plugins/preview';
import 'tinymce/plugins/hr';
import 'tinymce/plugins/anchor';
import 'tinymce/plugins/pagebreak';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/wordcount';
import 'tinymce/plugins/visualblocks';
import 'tinymce/plugins/visualchars';
import 'tinymce/plugins/code';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/insertdatetime';
import 'tinymce/plugins/media';
import 'tinymce/plugins/nonbreaking';
import 'tinymce/plugins/save';
import 'tinymce/plugins/table';
import 'tinymce/plugins/contextmenu';
import 'tinymce/plugins/directionality';
import 'tinymce/plugins/emoticons';
import 'tinymce/plugins/template';
import 'tinymce/plugins/paste';
import 'tinymce/plugins/textcolor';
import 'tinymce/plugins/colorpicker';
import 'tinymce/plugins/textpattern';
import 'tinymce/skins/lightgray/skin.min.css';
import 'tinymce/skins/lightgray/content.min.css';

const PLUGINS =
  'advlist autolink lists link image charmap print preview hr anchor pagebreak searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking save table contextmenu directionality emoticons template paste textcolor colorpicker textpattern';
const TOOLBAR =
  'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media';

export function RichText({
  id,
  name,
  value,
  onChange,
  height = 300,
}: {
  id: string;
  name: string;
  value: string;
  onChange: (html: string) => void;
  height?: number;
}) {
  return (
    <Editor
      id={id}
      value={value}
      onEditorChange={(html) => onChange(html)}
      init={{
        height,
        menubar: false,
        branding: false,
        relative_urls: false,
        plugins: PLUGINS,
        toolbar: TOOLBAR,
        file_browser_callback: (field_name: string, _url: string, type: string, _win: any) => {
          const cmsURL =
            '/filemanager?field_name=' + field_name + (type === 'image' ? '&type=Images' : '&type=Files');
          (window as any).tinyMCE.activeEditor.windowManager.open({
            file: cmsURL,
            title: 'Filemanager',
            width: window.innerWidth * 0.8,
            height: window.innerHeight * 0.8,
            resizable: 'yes',
            close_previous: 'no',
          });
        },
      }}
    />
  );
}
