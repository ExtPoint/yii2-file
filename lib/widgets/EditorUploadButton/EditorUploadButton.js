import FileUp from 'fileup-core';
import QueueCollection from 'fileup-core/lib/models/QueueCollection';

const CKEDITOR = window.CKEDITOR;

CKEDITOR.plugins.add('fileup', {
    init: function (editor) {
        // Do not execute this paste listener if it will not be possible to upload file.
        if (!CKEDITOR.plugins.clipboard.isFileApiSupported) {
            return;
        }

        const uploadUrl = CKEDITOR.fileTools.getUploadUrl(editor.config, 'image');
        if (!uploadUrl) {
            CKEDITOR.error('fileup-config');
            return;
        }

        editor.addCommand('image', {
            exec: function () {
                const uploader = new FileUp({
                    backendUrl: uploadUrl,
                });
                const element = new CKEDITOR.dom.element('div');
                element.setStyles({
                    padding: '50px',
                    textAlign: 'center',
                    background: '#eee'
                });

                uploader.queue.on(QueueCollection.EVENT_ADD, () => {
                    editor.insertElement(element);
                });
                uploader.queue.on(QueueCollection.EVENT_ITEM_STATUS, file => {
                    if (file.isStatusEnd()) {
                        if (file.isResultSuccess()) {
                            const url = file.getResultHttpMessage() && file.getResultHttpMessage().url;
                            if (url) {
                                element.setStyles({
                                    padding: 0,
                                    textAlign: 'left',
                                    background: ''
                                });
                                element.setHtml('<img src="' + url + '" width="100%" alt="" />');
                                return;
                            }
                        }

                        element.setStyles({
                            color: 'red'
                        });
                        element.setHtml(editor.lang.filetools.httpError.replace('%1', file.getResultHttpStatus()));
                    }
                });
                uploader.queue.on(QueueCollection.EVENT_ITEM_PROGRESS, file => {
                    element.setText(editor.lang.common.upload + '...' + file.progress.getPercent() + '%');
                    console.log(editor.lang.common.upload + '...' + file.progress.getPercent() + '%');
                });
                uploader.browse();
            }
        });
    }
});


