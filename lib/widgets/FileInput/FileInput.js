import React from 'react';
import PropTypes from 'prop-types';
import fileup from 'fileup-redux';
import FilePropType from 'fileup-redux/lib/types/FilePropType';
import _isFunction from 'lodash/isFunction';

import FileItem from './FileItem';

class FileInput extends React.Component {

    static propTypes = {
        name: PropTypes.string,
        multiple: PropTypes.bool,
        asArrayString: PropTypes.bool,
        buttonLabel: PropTypes.any,
        buttonComponent: PropTypes.oneOfType([
            PropTypes.element,
            PropTypes.func,
        ]),
        files: PropTypes.arrayOf(FilePropType),
    };

    static defaultProps = {
        name: 'file',
    };

    constructor(props) {
        super(props);

        this._onBrowseClick = this._onBrowseClick.bind(this);

        this._syncValue(props);
    }

    componentWillReceiveProps(nextProps) {
        this._syncValue(nextProps);
    }

    render() {
        return (
            <div>
                <div className='clearfix'>
                    {this.props.files.map(file => (
                        <FileItem
                            key={file.uid}
                            file={file}
                            name={!this.props.asArrayString && !this.props.input ? this.props.name + (this.props.multiple ? '[]' : '') : null}
                            onRemove={() => this.props.remove(file.uid)}
                        />
                    ))}
                    {!this.props.asArrayString && this.props.files.length === 0 && (
                        <input
                            name={this.props.name}
                            type='hidden'
                        />
                    )}
                    {this.props.asArrayString && (
                        <input
                            name={this.props.name}
                            type='hidden'
                            defaultValue={this.props.files
                                .map(file => file.resultHttpMessage && file.resultHttpMessage.uid)
                                .filter(Boolean)
                                .join(', ')}
                        />
                    )}
                </div>
                <div>
                    {this._renderButton()}
                </div>
            </div>
        );
    }

    _renderButton() {
        const props = {
            onClick: this._onBrowseClick
        };

        if (this.props.buttonComponent) {
            if (_isFunction(this.props.buttonComponent)) {
                const ButtonComponent = this.props.buttonComponent;
                return (
                    <ButtonComponent {...props} />
                );
            } else {
                return React.cloneElement(this.props.buttonComponent, props);
            }
        } else {
            return (
                <button
                    type='button'
                    className='btn btn-primary'
                    onClick={() => this.props.uploader.browse()}
                >
                    {this.props.buttonLabel || (this.props.multiple ? 'Прикрепить файлы' : 'Прикрепить файл')}
                </button>
            );
        }
    }

    _onBrowseClick(e) {
        e.preventDefault();
        this.props.uploader.browse();
    }

    _syncValue(props) {
        const prevInputIds = [].concat((this.props.input ? this.props.input.value : this.props.value) || []);
        const inputIds = [].concat((props.input ? props.input.value : props.value) || []);
        const uploaderIds = props.files
            .map(file => file.resultHttpMessage && file.resultHttpMessage.id || null)
            .filter(Boolean);

        // Remove files on value change (by form, for example - reset)
        if (prevInputIds.join() !== inputIds.join()) {
            const removeIds = prevInputIds.filter(id => inputIds.indexOf(id) === -1);
            const removeUids = props.files
                .filter(file => file.resultHttpMessage && removeIds.indexOf(file.resultHttpMessage.id) !== -1)
                .map(file => file.uid);
            this.props.remove(removeUids);
        }

        // Add new ids from files
        if (this.props.input && inputIds.join() !== uploaderIds.join()) {
            this.props.input.onChange(uploaderIds);
        }
    }

}

export default __appWidget.register('\\extpoint\\yii2\\file\\widgets\\FileInput\\FileInput',
    fileup({
        backendUrl: '/file/upload/',
    })(FileInput)
);