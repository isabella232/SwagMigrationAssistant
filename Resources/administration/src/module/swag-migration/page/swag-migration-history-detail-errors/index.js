import { Component, Mixin } from 'src/core/shopware';
import template from './swag-migration-history-detail-errors.html.twig';
import './swag-migration-history-detail-errors.scss';

Component.register('swag-migration-history-detail-errors', {
    template,

    mixins: [
        Mixin.getByName('listing')
    ],

    props: {
        migrationRun: {
            type: Object,
            required: true
        }
    },

    data() {
        return {
            isLoading: true,
            migrationErrors: [],
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            disableRouteParams: true,
            limit: 10
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    computed: {
        columns() {
            return [
                {
                    property: 'type',
                    dataIndex: 'type',
                    label: this.$t('swag-migration.index.errorType'),
                    allowResize: true
                },
                {
                    property: 'logEntry.code',
                    dataIndex: 'logEntry.code',
                    label: this.$t('swag-migration.index.errorDescription'),
                    primary: true,
                    allowResize: true
                }
            ];
        }
    },

    methods: {
        getList() {
            this.isLoading = true;
            const params = this.getListingParams();

            return this.migrationRun.getAssociation('logs').getList(params).then((response) => {
                this.total = response.total;
                this.migrationErrors = response.items;
                this.isLoading = false;
                return this.migrationErrors;
            });
        },

        getErrorTitleSnippet(item) {
            const snippetKey = item.titleSnippet;
            if (this.$te(snippetKey)) {
                return snippetKey;
            }

            return 'swag-migration.index.error.unknownError';
        },

        getErrorDescriptionSnippet(item) {
            const snippetKey = item.descriptionSnippet;
            if (this.$te(snippetKey)) {
                return snippetKey;
            }

            return 'swag-migration.index.error.unknownError';
        }
    }
});
