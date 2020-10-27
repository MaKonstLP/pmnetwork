export const DRAFT_ID = $('[data-ctor-draft-id]').data('ctor-draft-id');
export const PREVIEW_LINK = $('[data-ctor-preview-link]').data(
    'ctor-preview-link'
);
export const SAVE_LINK = $('[data-ctor-save-link]').data('ctor-save-link');

export const DRAFT_REL_FIELD = 'blog_post_id';
export const BLOCK_REL_FIELD = 'blog_block_id';
export const BLOCK_REL_NAME = 'blogBlock';

export const GET_BLOCKS =
    '/blog-blocks/?fields=id,name,type&expand=blockTypeLabel';
export const API_DRAFT_BLOCKS = '/blog-post-blocks/';
export const GET_DRAFT_BLOCKS = `${API_DRAFT_BLOCKS}?expand=${BLOCK_REL_NAME},mediaTargets`;
export const POST_BLOCKS_SORTINGS = '/blog-post-blocks/sort/';

export const POST_FILE = '/media/upload/';
export const POST_FILE_FASTVIEW = '/media/fast-view/';

export const BLOCK_SHARED_SETTINGS = [
    {
        title: 'Ширина блока',
        slug: 'size',
        type: 'select',
        variants: [
            { value: 's', label: 'Маленький', default: 1 },
            { value: 'm', label: 'Обычный' },
            { value: 'l', label: 'Большой' },
        ],
    },
    {
        title: 'Цветовая тема',
        slug: 'color',
        type: 'select',
        variants: [
            { value: 'default', label: 'Стандартный', default: 1 },
            { value: 'blue', label: 'Синий' },
            { value: 'yellow', label: 'Желтый' },
            { value: 'gray', label: 'Серый' },
        ],
    },
    {
        title: 'Отступы',
        slug: 'margin',
        type: 'select',
        variants: [
            { value: 'default', label: 'Обычный', default: 1 },
            { value: 'large', label: 'Большой' },
            { value: 'nomargin', label: 'Без отступов' },
        ],
    },
];
