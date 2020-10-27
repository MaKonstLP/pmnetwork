import './constructor.css';
import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom';
import { SortableContainer, SortableElement } from 'react-sortable-hoc';
import { Editor as TinyMceEditor } from '@tinymce/tinymce-react';
import arrayMove from 'array-move';
import Select from 'react-select';
import _ from 'lodash';
import CyrillicToTranslit from 'cyrillic-to-translit-js';
import { Line } from 'rc-progress';
import Checkbox from 'rc-checkbox';
import Sticky from 'react-stickynode';

import {
    BLOCK_SHARED_SETTINGS,
    DRAFT_REL_FIELD,
    BLOCK_REL_FIELD,
    BLOCK_REL_NAME,
    GET_BLOCKS,
    GET_DRAFT_BLOCKS,
    POST_BLOCKS_SORTINGS,
    API_DRAFT_BLOCKS,
    POST_FILE,
    POST_FILE_FASTVIEW,
    DRAFT_ID,
    PREVIEW_LINK,
    SAVE_LINK,
} from './settings';

const PLAIN_TEXT_INPUT = 'text';
const IMAGE_INPUT = 'image'; //TODO expand
const RICH_TEXT_INPUT = 'html';

const PlainTextInput = ({ initialValue, onBlur, onInput, onInputReady }) => {
    const [loaded, setLoaded] = useState(false);
    useEffect(() => {
        if (loaded) {
            onInputReady(true);
        }
    }, [loaded]);
    return (
        <div>
            <TinyMceEditor
                onBlur={onBlur}
                initialValue={initialValue}
                init={{
                    height: 110,
                    menubar: false,
                    plugins: [
                        'paste',
                        'autoresize',
                        'emoticons',
                        'charmap',
                        'nonbreaking',
                        'link',
                    ],
                    autoresize_bottom_margin: 10,
                    default_link_target: '_blank',
                    toolbar:
                        'undo redo | bold italic forecolor backcolor | link emoticons charmap nonbreaking',
                    nowrap: true,
                    forced_root_block: '',
                    force_br_newlines: true,
                    force_p_newlines: false,
                    className: 'form-control',
                    language: 'ru',
                    paste_as_text: true,
                }}
                inline={true}
                onEditorChange={onInput}
                onInit={() => setLoaded(true)}
            />
        </div>
    );
};

const RichTextInput = ({
    initialValue,
    onBlur,
    onInput,
    onInputReady,
    mediaTargetId,
}) => {
    const [loaded, setLoaded] = useState(false);
    const initEditor = (editor) => {
        editor.hide();
        $(editor.getElement()).on('focus', () => {
            editor.show();
            editor.focus();
        });

        editor.on('blur', () => editor.hide());
        setLoaded(true);
    };
    useEffect(() => {
        if (loaded) {
            onInputReady(true);
        }
    }, [loaded]);
    return (
        <div>
            <TinyMceEditor
                onBlur={onBlur}
                initialValue={initialValue}
                init={{
                    menubar:
                        'file edit insert view format table tools help custom',
                    autoresize_bottom_margin: 10,
                    language: 'ru',
                    plugins: [
                        'advlist autolink lists link charmap preview anchor emoticons nonbreaking',
                        'searchreplace visualblocks code fullscreen autoresize',
                        'insertdatetime media table paste image',
                    ],
                    convert_urls: false,
                    image_caption: true,
                    default_link_target: '_blank',
                    toolbar:
                        'undo redo | bold italic forecolor backcolor|' +
                        ' aligncenter alignright alignjustify |' +
                        'bullist numlist outdent indent | image link |' +
                        'emoticons charmap nonbreaking',
                    nowrap: true,
                    className: 'form-control',
                    images_upload_handler: function (
                        blobInfo,
                        success,
                        failure,
                        progress
                    ) {
                        const onSuccess = (response) => {
                            if (
                                Array.isArray(response.initialPreview) &&
                                response.initialPreview.length > 0
                            ) {
                                success(response.initialPreview[0]);
                            } else failure(JSON.stringify(response));
                        };
                        const onError = (response) => {
                            failure(JSON.stringify(response));
                        };

                        const formData = new FormData();
                        formData.append(
                            'file',
                            blobInfo.blob(),
                            blobInfo.filename()
                        );
                        formData.append('media_target_id', mediaTargetId);

                        $.ajax({
                            xhr: function () {
                                var xhr = new XMLHttpRequest();
                                xhr.upload.addEventListener(
                                    'progress',
                                    function (e) {
                                        if (e.lengthComputable) {
                                            progress(
                                                (e.loaded / e.total) * 100
                                            );
                                        }
                                    },
                                    false
                                );
                                return xhr;
                            },
                            method: 'post',
                            url: POST_FILE,
                            data: formData,
                            success: onSuccess,
                            error: onError,
                            processData: false,
                            contentType: false,
                        });
                    },
                    menu: {
                        custom: { title: 'Выделение', items: 'wsnobreak' },
                    },
                    setup: (editor) => {
                        editor.ui.registry.addMenuItem('wsnobreak', {
                            text: 'Сделать выделенный текст неразрывным',
                            onAction: () => {
                                editor.focus();
                                var text = editor.selection.getContent({
                                    format: 'html',
                                });
                                if (text && text.length > 0) {
                                    editor.execCommand(
                                        'mceInsertContent',
                                        false,
                                        '<span style="white-space: nowrap;">' +
                                            text +
                                            '</span>'
                                    );
                                }
                            },
                        });
                    },
                }}
                // inline={true}
                onEditorChange={onInput}
                onInit={(e, editor) => {
                    initEditor(editor);
                }}
            />
        </div>
    );
};

const FileInput = ({ mediaTargetId, onInputReady }) => {
    const inputref = React.createRef();
    const [loaded, setLoaded] = useState(false);

    useEffect(() => {
        const close = (elem) => (response) => {
            $(elem).mediaInputGen(response, () => setLoaded(true));
        };
        $.ajax({
            url: POST_FILE_FASTVIEW,
            type: 'post',
            dataType: 'json',
            data: { media_target_id: mediaTargetId },
            success: close(inputref.current),
            error: function (response) {
                console.error(response);
            },
        });
    }, [mediaTargetId]);

    useEffect(() => {
        if (loaded) {
            onInputReady(true);
        }
    }, [loaded]);

    return (
        <input
            type='file'
            className='media_upload'
            name=''
            multiple
            data-media_target_id={mediaTargetId}
            ref={inputref}
        />
    );
};

const SortableItem = SortableElement(
    ({
        item: draftBlock,
        ajax,
        onDeleteBlock,
        onExpandedStateChange,
        onBlockLoaded,
    }) => {
        let inputsMeta = {},
            initialInputsContent = _.assign(
                {},
                {
                    paragraph: {
                        isset: false,
                        asHeading: false,
                        heading: '',
                        alias: '',
                        headingVar: '',
                        level: 1,
                    },
                }
            ),
            inputsReadyInitState = {},
            blockSettings = [];
        const addToBlockSettings = ({ title, slug, type, variants }) => {
            const settingVarName = `setting_${slug}`;
            const defaultVariant = variants.find((variant) => variant.default);
            if (defaultVariant)
                initialInputsContent[settingVarName] = defaultVariant.value;
            blockSettings = [
                ...blockSettings,
                {
                    title,
                    type,
                    variants,
                    settingVarName,
                },
            ];
        };
        BLOCK_SHARED_SETTINGS.forEach((setting) => {
            addToBlockSettings(setting);
        });
        try {
            inputsMeta = JSON.parse(draftBlock[BLOCK_REL_NAME].inputs) || {};
            for (const inputType of Object.keys(inputsMeta)) {
                if (inputType == 'settings') {
                    inputsMeta[inputType].forEach((setting) => {
                        addToBlockSettings(setting);
                    });
                } else {
                    for (const {
                        heading = null,
                        slug,
                        titlePreview = false,
                    } of inputsMeta[inputType]) {
                        const inputVarName = `${inputType}_${slug}`;
                        inputsReadyInitState = _.assign(
                            {},
                            inputsReadyInitState,
                            {
                                [inputVarName]: false,
                            }
                        );
                        if (heading) {
                            initialInputsContent = _.assign(
                                {},
                                initialInputsContent,
                                {
                                    paragraph: {
                                        ...initialInputsContent.paragraph,
                                        isset: true,
                                        asHeading: true,
                                        level: heading,
                                        headingVar: inputVarName,
                                    },
                                }
                            );
                        }
                        if (titlePreview) {
                            initialInputsContent = _.assign(
                                {},
                                initialInputsContent,
                                {
                                    titlePreviewInput: inputVarName,
                                }
                            );
                        }
                    }
                }
            }
            if (draftBlock.content) {
                const parsed = JSON.parse(draftBlock.content) || {};
                initialInputsContent = _.assign(
                    {},
                    initialInputsContent,
                    parsed
                );
            }
        } catch (error) {
            return <li>{error.message}</li>;
        }

        const [inputsContent, setInputContent] = useState(initialInputsContent);
        const [inputsReadyState, setInputsReadyState] = useState(
            inputsReadyInitState
        );

        const [isDirty, setIsDirty] = useState(false);
        const [mode, setMode] = useState('editor');

        const getStrippedTextFromHtml = (html) => {
            return new DOMParser().parseFromString(html, 'text/html')
                .documentElement.textContent;
        };

        const initialTitlePreview =
            (inputsContent[inputsContent.titlePreviewInput] &&
                getStrippedTextFromHtml(
                    inputsContent[inputsContent.titlePreviewInput]
                )) ||
            '';

        const [titlePreview, setTitlePreview] = useState(initialTitlePreview);

        const headingVar = inputsContent.paragraph.headingVar;

        const updateTitlePreview = (html) => {
            const stripped = getStrippedTextFromHtml(html);
            setTitlePreview(stripped);
        };

        const setParagraphAsHeading = (newinputsContent) => {
            const html = newinputsContent[headingVar];
            const stripped = getStrippedTextFromHtml(html);
            newinputsContent.paragraph.heading = stripped;
            newinputsContent.paragraph.alias = CyrillicToTranslit()
                .transform(stripped, '-')
                .replace(/[^a-zA-Z-]/g, '')
                .toLowerCase();
        };

        const onInput = ({ alias, data }) => {
            const newinputsContent = _.cloneDeep(inputsContent);
            newinputsContent[alias] = data;
            if (
                headingVar != null &&
                alias == headingVar &&
                inputsContent.paragraph.isset &&
                inputsContent.paragraph.asHeading
            ) {
                setParagraphAsHeading(newinputsContent);
            }
            setIsDirty(true);
            setInputContent(newinputsContent);
        };

        const onParagraphCheckbox = (e) => {
            const checked = e.target.checked;
            const newinputsContent = _.cloneDeep(inputsContent);
            newinputsContent.paragraph.isset = checked;
            if (checked && inputsContent.paragraph.asHeading) {
                setParagraphAsHeading(newinputsContent);
            }
            setInputContent(newinputsContent);
        };

        const onParagraphAsHeadingCheckbox = (e) => {
            const checked = e.target.checked;
            const newinputsContent = _.cloneDeep(inputsContent);
            newinputsContent.paragraph.asHeading = checked;
            if (checked) {
                setParagraphAsHeading(newinputsContent);
            }
            setInputContent(newinputsContent);
        };

        const onParagraphHeadingChange = (e) => {
            const value = e.target.value;
            const alias = CyrillicToTranslit()
                .transform(value, '-')
                .replace(/[^a-zA-Z-]/g, '')
                .toLowerCase();
            const newinputsContent = _.cloneDeep(inputsContent);
            newinputsContent.paragraph.heading = value;
            newinputsContent.paragraph.alias = alias;
            setInputContent(newinputsContent);
        };

        const onSettingChange = ({ settingVarName, value }) => {
            setInputContent(
                _.assign(_.cloneDeep(inputsContent), {
                    [settingVarName]: value,
                })
            );
        };

        const onSave = () => {
            ajax(
                'put',
                API_DRAFT_BLOCKS + draftBlock.id + '/',
                (res) => {
                    setIsDirty(false);
                },
                { content: JSON.stringify(inputsContent) },
                (res) => {
                    setIsDirty(false);
                }
            );
        };

        const onInputReady = (varName) => (state) => {
            setInputsReadyState(
                _.assign(_.cloneDeep(inputsReadyState), {
                    [varName]: state,
                })
            );
        };

        useEffect(() => {
            if (!isDirty) {
                onSave();
            }
        }, [isDirty, inputsContent]);

        useEffect(() => {
            const isLoaded = !_.values(inputsReadyState).includes(false);
            if (isLoaded) {
                setIsDirty(false);
            }
            onBlockLoaded(isLoaded);
        }, [inputsReadyState]);

        const inputComponents = Object.keys(inputsMeta).reduce(
            (acc, inputType) => {
                let inputs = [];
                switch (inputType) {
                    case PLAIN_TEXT_INPUT:
                        inputs = inputsMeta[inputType].map(
                            ({ title, slug }, idx) => {
                                const contentVarName = `${inputType}_${slug}`;
                                const initialValue =
                                    (inputsContent[contentVarName] &&
                                        inputsContent[contentVarName]) ||
                                    '';
                                return {
                                    title,
                                    inputElement: (
                                        <PlainTextInput
                                            initialValue={initialValue}
                                            onInput={(data) => {
                                                titlePreview &&
                                                    updateTitlePreview(data);
                                                onInput({
                                                    alias: contentVarName,
                                                    data,
                                                });
                                            }}
                                            onBlur={onSave}
                                            onInputReady={onInputReady(
                                                contentVarName
                                            )}
                                        />
                                    ),
                                };
                            }
                        );
                        break;
                    case RICH_TEXT_INPUT:
                        inputs = inputsMeta[inputType].map(
                            ({ title, slug }, idx) => {
                                const contentVarName = `${inputType}_${slug}`;
                                const initialValue =
                                    (inputsContent[contentVarName] &&
                                        inputsContent[contentVarName]) ||
                                    '';
                                const mediaTarget = draftBlock.mediaTargets.find(
                                    (item) => item.type == contentVarName
                                );
                                return {
                                    title,
                                    inputElement: (
                                        <RichTextInput
                                            initialValue={initialValue}
                                            onInput={(data) => {
                                                titlePreview &&
                                                    updateTitlePreview(data);
                                                onInput({
                                                    alias: contentVarName,
                                                    data,
                                                });
                                            }}
                                            onBlur={onSave}
                                            onInputReady={onInputReady(
                                                contentVarName
                                            )}
                                            ajax={ajax}
                                            mediaTargetId={
                                                mediaTarget && mediaTarget.id
                                            }
                                        />
                                    ),
                                };
                            }
                        );
                        break;
                    case IMAGE_INPUT:
                        inputs = inputsMeta[inputType].map(
                            ({ title, slug }, idx) => {
                                const contentVarName = `${inputType}_${slug}`;
                                const mediaTarget = draftBlock.mediaTargets.find(
                                    (item) => item.type == contentVarName
                                );
                                return {
                                    title,
                                    inputElement: (
                                        <FileInput
                                            mediaTargetId={
                                                mediaTarget && mediaTarget.id
                                            }
                                            onInputReady={onInputReady(
                                                contentVarName
                                            )}
                                        />
                                    ),
                                };
                            }
                        );
                        break;
                    default:
                        break;
                }
                return [...acc, ...inputs];
            },
            []
        );

        const onChangeMode = (newMode) => {
            if (newMode == mode) {
                onExpandedStateChange({
                    body: !draftBlock.expanded.body,
                });
            } else {
                setMode(newMode);
                if (!draftBlock.expanded.body) {
                    onExpandedStateChange({
                        body: true,
                    });
                }
            }
        };

        return (
            <li className='editor__block' key={draftBlock.id}>
                <div
                    className={`box box-primary ${
                        draftBlock.expanded.body ? '' : 'collapsed-box'
                    }`}
                >
                    <div
                        className='box-header with-border'
                        style={{ display: 'flex' }}
                    >
                        <div
                            className='box-title'
                            title={draftBlock.id}
                            id={draftBlock.id}
                            onClick={() => {
                                onExpandedStateChange({
                                    body: !draftBlock.expanded.body,
                                });
                            }}
                        >
                            <h5>{draftBlock[BLOCK_REL_NAME].name}</h5>
                        </div>
                        <div
                            className={`title-elipsis ${
                                draftBlock.expanded.body ? 'hidden' : ''
                            }`}
                        >
                            {titlePreview}
                        </div>
                        <div
                            className='btn-group'
                            style={{ marginLeft: 'auto', marginRight: 30 }}
                        >
                            <button
                                type='button'
                                onClick={() => {
                                    onChangeMode('editor');
                                }}
                                className={`btn btn-default ${
                                    mode == 'editor' &&
                                    draftBlock.expanded.body &&
                                    'bg-gray'
                                }`}
                            >
                                <i className={`fa fa-paragraph`}></i>
                            </button>
                            <button
                                type='button'
                                className={`btn btn-default ${
                                    mode == 'settings' &&
                                    draftBlock.expanded.body &&
                                    'bg-gray'
                                }`}
                                onClick={() => {
                                    onChangeMode('settings');
                                }}
                            >
                                <i className='fa fa-gear'></i>
                            </button>
                        </div>
                        <div className='btn-group'>
                            <button
                                type='button'
                                onClick={onSave}
                                disabled={!isDirty}
                                className={`btn btn-default ${
                                    isDirty && 'bg-olive'
                                }`}
                            >
                                <i className={`fa fa-save`}></i>
                            </button>
                            <button type='button' className='btn btn-default'>
                                <i className='fa fa-refresh'></i>
                            </button>
                            <button
                                type='button'
                                className={`btn btn-default drag-btn`}
                            >
                                <i className={`fa fa-arrows drag-btn`}></i>
                            </button>
                            <button
                                type='button'
                                className='btn btn-default'
                                onClick={onDeleteBlock}
                            >
                                <i className='fa fa-close'></i>
                            </button>
                        </div>
                    </div>
                    <div
                        className={`box-body ${
                            mode == 'editor' ? '' : 'hidden'
                        }`}
                    >
                        {inputComponents.map(({ title, inputElement }, idx) => (
                            <div className='form-group' key={idx}>
                                <label>{title}</label>
                                {inputElement}
                            </div>
                        ))}
                    </div>
                    <div
                        className={`box-body ${
                            mode == 'settings' ? '' : 'hidden'
                        }`}
                    >
                        <div className='box-body'>
                            {blockSettings.map(
                                ({ title, type, variants, settingVarName }) =>
                                    type == 'select' && (
                                        <div
                                            className='form-group_row'
                                            key={settingVarName}
                                        >
                                            <div className=''>{title}</div>
                                            <Select
                                                defaultValue={variants.find(
                                                    ({ value }) =>
                                                        value ==
                                                        inputsContent[
                                                            settingVarName
                                                        ]
                                                )}
                                                options={variants}
                                                onChange={({ value }) =>
                                                    onSettingChange({
                                                        settingVarName,
                                                        value,
                                                    })
                                                }
                                                isSearchable={false}
                                            />
                                        </div>
                                    )
                            )}
                            <div className='form-group_row form-group_row__checkbox'>
                                <label>
                                    <span>Выводить в оглавление</span>
                                    <Checkbox
                                        onChange={onParagraphCheckbox}
                                        defaultChecked={
                                            inputsContent.paragraph.isset
                                        }
                                    />
                                </label>
                                <label
                                    className={
                                        inputsContent.paragraph.isset &&
                                        headingVar != ''
                                            ? ''
                                            : 'hidden_soft'
                                    }
                                >
                                    <span>Пункт оглавления = заголовку</span>
                                    <Checkbox
                                        onChange={onParagraphAsHeadingCheckbox}
                                        defaultChecked={
                                            inputsContent.paragraph.asHeading
                                        }
                                    />
                                </label>
                            </div>
                            {inputsContent.paragraph.isset &&
                                !inputsContent.paragraph.asHeading && (
                                    <div className='form-group'>
                                        <label htmlFor=''>
                                            Введите название пункта в содержании
                                        </label>
                                        <input
                                            type='text'
                                            className='form-control'
                                            placeholder='Название пункта в содержании'
                                            onChange={onParagraphHeadingChange}
                                            defaultValue={
                                                inputsContent.paragraph.heading
                                            }
                                        />
                                    </div>
                                )}
                        </div>
                    </div>
                </div>
            </li>
        );
    }
);

const SortableList = SortableContainer(({ children }) => {
    return <ul>{children}</ul>;
});

const Editor = ({
    ajax,
    newBlock,
    setNewBlock,
    shouldAllBlocksExpand,
    setShouldAllBlocksExpand,
    shouldBlocksCollapseOnAction,
}) => {
    const [draftBlocks, setDraftBlocks] = useState([]);
    const setNewDraftBlocks = (newDraftBlocks) => {
        const withExpandedState = newDraftBlocks.map((draftBlock, idx, arr) => {
            const blockExpandedState =
                shouldBlocksCollapseOnAction || draftBlock.expanded == undefined
                    ? { body: idx === arr.length - 1 }
                    : draftBlock.expanded;
            return {
                ...draftBlock,
                expanded: blockExpandedState,
            };
        });
        setDraftBlocks(withExpandedState);
    };

    const addBlock = (item) => {
        setNewDraftBlocks([...draftBlocks, item]);
        refreshSortings();
    };
    const removeBlock = (id) => {
        ajax('delete', API_DRAFT_BLOCKS + id + '/', (data, textStatus, xhr) => {
            if (xhr.status == 204) {
                setNewDraftBlocks(draftBlocks.filter((item) => item.id !== id));
            }
        });
    };
    const onDeleteBlock = (id) => () => {
        const result = confirm('Вы уверены что хотите удалить блок?');
        if (result) {
            removeBlock(id);
        }
    };
    const refreshSortings = () => {
        const updateData = draftBlocks.reduce((acc, draftBlock, idx) => {
            const sortIndex = idx + 1;
            if (draftBlock.sort != sortIndex) {
                return { ...acc, [draftBlock.id]: sortIndex };
            }
            return acc;
        }, {});
        if (!_.isEmpty(updateData)) {
            ajax(
                'post',
                POST_BLOCKS_SORTINGS,
                (rowsUpdated) => {
                    if (_.size(updateData) == rowsUpdated) {
                        setNewDraftBlocks(
                            draftBlocks.map((item, idx) => {
                                item.sort = idx + 1;
                                return item;
                            })
                        );
                    }
                },
                { items: JSON.stringify(updateData) },
                (res) => console.log(res)
            );
        }
    };
    const createNewDraftBlock = () => {
        if (newBlock !== null) {
            ajax(
                'post',
                GET_DRAFT_BLOCKS,
                (res) => {
                    addBlock(res);
                },
                {
                    [DRAFT_REL_FIELD]: DRAFT_ID,
                    [BLOCK_REL_FIELD]: newBlock.id,
                },
                (res) => {},
                setNewBlock(null)
            );
        }
    };

    const expandAllBlocks = (state) => {
        const newDraftBlocks = draftBlocks.map((draftBlock, idx, arr) => ({
            ...draftBlock,
            expanded: { body: state },
        }));
        setDraftBlocks(newDraftBlocks);
    };

    useEffect(() => {
        createNewDraftBlock();
    }, [newBlock]);

    useEffect(() => {
        if (shouldAllBlocksExpand !== null) {
            expandAllBlocks(shouldAllBlocksExpand);
            setShouldAllBlocksExpand(null);
        }
    }, [shouldAllBlocksExpand]);

    useEffect(
        () =>
            ajax(
                'get',
                `${GET_DRAFT_BLOCKS}&filter[${DRAFT_REL_FIELD}]=${DRAFT_ID}`,
                (res) => setNewDraftBlocks(res.items || [])
            ),
        []
    );

    useEffect(() => {
        refreshSortings();
    }, [draftBlocks]);

    const onSortEnd = ({ oldIndex, newIndex, collection, isKeySorting }) => {
        const sortedBlocks = arrayMove(draftBlocks, oldIndex - 1, newIndex - 1);
        setNewDraftBlocks(sortedBlocks);
    };

    const shouldCancelStart = (e) => {
        if (!e.target.classList.contains('drag-btn')) {
            return true;
        }
    };

    const onExpandedStateChange = (id) => ({ body }) => {
        const newDraftBlocks = draftBlocks.map((draftBlock) => {
            if (id == draftBlock.id) {
                return { ...draftBlock, expanded: { body } };
            }
            return draftBlock;
        });
        setDraftBlocks(newDraftBlocks);
    };

    const onBlockLoaded = (id) => (state) => {
        const newDraftBlocks = draftBlocks.map((draftBlock) => {
            if (id == draftBlock.id) {
                return { ...draftBlock, loaded: state };
            }
            return draftBlock;
        });
        setDraftBlocks(newDraftBlocks);
    };

    const percentOfBlocksLoaded =
        draftBlocks.length > 0
            ? (100 / draftBlocks.length) *
              draftBlocks.filter((block) => block.loaded).length
            : 0;
    const isFullyLoaded = Math.round(percentOfBlocksLoaded) === 100;

    return (
        <div className='constructor__editor editor'>
            <div
                className={`progress-bar-container ${
                    isFullyLoaded ? 'hidden' : ''
                }`}
            >
                {percentOfBlocksLoaded > 0 ? (
                    <Line
                        percent={percentOfBlocksLoaded}
                        strokeWidth='5'
                        strokeColor='#2D6BC0'
                        style={{ width: '50%' }}
                    />
                ) : (
                    ''
                )}
            </div>
            <div className={!isFullyLoaded ? 'hidden' : ''}>
                <SortableList
                    onSortEnd={onSortEnd}
                    shouldCancelStart={shouldCancelStart}
                >
                    {draftBlocks.map((draftBlock, idx) => (
                        <SortableItem
                            key={`item_${draftBlock.id}`}
                            index={draftBlock.sort}
                            item={draftBlock}
                            ajax={ajax}
                            onDeleteBlock={onDeleteBlock(draftBlock.id)}
                            onExpandedStateChange={onExpandedStateChange(
                                draftBlock.id
                            )}
                            onBlockLoaded={onBlockLoaded(draftBlock.id)}
                        />
                    ))}
                </SortableList>
            </div>
        </div>
    );
};

const ToolsPanel = ({
    ajax,
    onBlockBtnClick,
    setShouldAllBlocksExpand,
    shouldBlocksCollapseOnAction,
    setShouldBlocksCollapseOnAction,
}) => {
    const [availableBlocks, setAvailableBlocks] = useState([]);
    useEffect(() => {
        ajax('get', GET_BLOCKS, (res) => {
            setAvailableBlocks(res.items || []);
        });
    }, []);
    const groups = availableBlocks.reduce((acc, block) => {
        return {
            ...acc,
            [block.type]: {
                label: block.blockTypeLabel,
                items: [
                    ...((acc[block.type] && acc[block.type].items) || []),
                    block,
                ],
            },
        };
    }, {});
    return (
        <div className='constructor__tools-panel'>
            <Sticky enabled={true} top={50} innerClass='tools-panel'>
                <div className='form_group'>
                    <button
                        title='Применяет текущие изменения для уже опубликованного
                            поста. Не нужно нажимать для сохранения состояния
                            конструктора!'
                        className='btn btn-m mr-5 mb-5 btn-primary draft-save'
                        onClick={() => ajax('patch', SAVE_LINK)}
                    >
                        Применить изменения
                    </button>
                    <a target='_blank' href={`${PREVIEW_LINK}`}>
                        <button
                            className='btn btn-sm mr-5 mb-5 btn-default'
                        >
                            Предпросмотр&nbsp;
                            <i className='fa fa-external-link'></i>
                        </button>
                    </a>
                </div>
                {_.keys(groups).map((key, idx) => {
                    const { label, items } = groups[key];
                    return (
                        <div className='form_group' key={idx}>
                            <label>{label}</label>
                            <ul>
                                {items.map((block, idx) => (
                                    <li key={idx}>
                                        <button
                                            className='btn mr-5 mb-5 btn-default'
                                            onClick={(e) =>
                                                onBlockBtnClick(block)
                                            }
                                        >
                                            {block.name}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    );
                })}
                <div className='form_group'>
                    <label>Настройки</label>
                    <div className='form-group_row form-group_row__checkbox form-group_row__checkbox_small'>
                        <label>
                            <span>Сворачивать блоки при действиях</span>
                            <Checkbox
                                onChange={(e) => {
                                    setShouldBlocksCollapseOnAction(
                                        e.target.checked
                                    );
                                    const localStorageValue = e.target.checked
                                        ? 'true'
                                        : 'false';
                                    localStorage.setItem(
                                        'shouldBlocksCollapseOnAction',
                                        localStorageValue
                                    );
                                }}
                                defaultChecked={shouldBlocksCollapseOnAction}
                            />
                        </label>
                    </div>
                    <div>
                        <button
                            className='btn btn-sm mr-5 mb-5 btn-default'
                            onClick={(e) => {
                                setShouldAllBlocksExpand(false);
                            }}
                        >
                            Свернуть все
                        </button>
                        <button
                            className='btn btn-sm mr-5 mb-5 btn-default'
                            onClick={(e) => {
                                setShouldAllBlocksExpand(true);
                            }}
                        >
                            Развернуть все
                        </button>
                    </div>
                </div>
            </Sticky>
        </div>
    );
};

const App = () => {
    const [isFetching, setIsFetching] = useState(false);
    const [newBlock, setNewBlock] = useState(null);
    const [shouldAllBlocksExpand, setShouldAllBlocksExpand] = useState(null);
    const initialShouldBlocksCollapseOnAction =
        localStorage.getItem('shouldBlocksCollapseOnAction') !== 'false';
    const [
        shouldBlocksCollapseOnAction,
        setShouldBlocksCollapseOnAction,
    ] = useState(initialShouldBlocksCollapseOnAction);

    const ajax = (
        method,
        url,
        success = () => {},
        data = {},
        error = (err) => {
            console.log(err);
        },
        callback = () => {}
    ) => {
        if (!isFetching) {
            setIsFetching(true);
            console.log('sending ' + method, data);
            $.ajax({
                url,
                type: method,
                method,
                cache: false,
                data,
                dataType: 'json',
                success: (res, textStatus, xhr) => {
                    setIsFetching(false);
                    success(res, textStatus, xhr);
                },
                error: (res) => {
                    setIsFetching(false);
                    error(res);
                },
            });
        }
        callback();
    };
    const onBlockBtnClick = (block) => {
        setNewBlock(block);
    };

    const props = {
        onBlockBtnClick,
        ajax,
        newBlock,
        setNewBlock,
        shouldAllBlocksExpand,
        setShouldAllBlocksExpand,
        shouldBlocksCollapseOnAction,
        setShouldBlocksCollapseOnAction,
    };
    return (
        <div className='constructor'>
            <ToolsPanel {...props} />
            <Editor {...props} />
        </div>
    );
};

$(() => {
    const domContainer = document.getElementById('react-constructor');
    if (DRAFT_ID) {
        ReactDOM.render(<App />, domContainer);
    }
});
