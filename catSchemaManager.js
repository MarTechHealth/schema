import { Dom } from 'https://lp-service-core.nyc3.digitaloceanspaces.com/lp-tools/tools-core/mod/dom.min.js';
import { BdApi } from 'https://lp-service-core.nyc3.digitaloceanspaces.com/lp-tools/tools-core/mod/bdApi.min.js';
import { Form } from 'https://lp-service-core.nyc3.digitaloceanspaces.com/lp-tools/tools-core/comp/form.min.js';
import { DynamicTable } from 'https://lp-service-core.nyc3.digitaloceanspaces.com/lp-tools/tools-core/comp/dynamicTable.min.js';
import { Dialog } from 'https://lp-service-core.nyc3.digitaloceanspaces.com/lp-tools/tools-core/comp/dialog.min.js';
import { Toast } from 'https://lp-service-core.nyc3.digitaloceanspaces.com/lp-tools/tools-core/comp/toast.min.js';
import { LayoutElement } from 'https://lp-service-core.nyc3.digitaloceanspaces.com/lp-tools/tools-core/comp/layoutElement.min.js';

function CatSchemaManager () {
    const dom = new Dom ();
    const bdApi = new BdApi ();
    const schemaForm = new Form ();
    const dialog = new Dialog ();
    const toast = new Toast ();
    const layoutElement = new LayoutElement ();

    let codeEditor;

    const layoutOptions = {
        mainWidget : `lp-schema-manager-api`
    };
    let dynamicTable;

    const layoutId = {
        container : `lp-cat-users-schema-tool-master-container`,
        table : {
            container : `lp-custmc-table-container`,
            dataTable : {
                filters : {
                    filterButton : `lp-custmc-tc-tdc-fc-filter-button`,
                    schemaFilter : {
                        container : `lp-custmc-tc-tdc-fc-schema-filter-container`,
                        input : `lp-custmc-tc-tdc-fc-sfc-input`,
                        label : `lp-custmc-tc-tdc-fc-sfc-label`,
                        iconCheck : `lp-custmc-tc-tdc-fc-sfc-icon`,
                        iconUnCheck : `lp-custmc-tc-tdc-fc-sfc-icon-uncheck`,
                        text : `lp-custmc-tc-tdc-fc-sfc-tet`
                    },
                    subscriptionSelect : `lp-custmc-tc-tdc-fc-subscription-select`
                }
            }
        },
        details : {
            container : `lp-custmc-details-container`,
            innerSlideContainer : `lp-custmc-dc-inner-slide-container`,
            title : `lp-custmc-dc-title`,
            memberPreview : {
                container : `lp-custmc-dc-member-preview-container`,
                thumbnail : `lp-custmc-dc-mpc-thumbnail`,
                name : `lp-custmc-dc-mpc-name`,
                closeButton : `lp-custmc-dc-mpc-close-button`
            },
            form : `lp-custmc-dc-form`,
            formElements : {
                textarea : `lp-custmc-dc-f-textarea`,
                editor : {
                    options : {
                        container : `lp-custmc-dc-f-editor-options-container`,
                        fullScreenButton : `lp-custmc-dc-f-eoc-full-screen-button`,
                        defaultSchemaButton : `lp-custmc-dc-f-eoc-default-schema-button`
                    },
                    element : `lp-custmc-dc-f-editor-element`
                },
                submitButton : `lp-custmc-dc-f-submit-button`
            }
        }
    };

    const layoutClass = {
        table : {
            row : {
                userInfo : {
                    container : `lp-custmc-tc-user-info-container`,
                    thumbnail : `lp-custmc-tc-uic-thumbnail`,
                    name : `lp-custmc-tc-uic-name`
                },
                schema : {
                    container : `lp-custmc-tc-schema-container`,
                    icon : `lp-custmc-tc-sc-icon`,
                    status : `lp-custmc-tc-sc-status`,
                    createdAt : `lp-custmc-tc-sc-created-at`,
                    updatedAt : `lp-custmc-tc-sc-updated-at`
                },
                actions : {
                    container : `lp-custmc-tc-actions-container`,
                    editButton : `lp-custmc-tc-ac-edit-button`
                }
            }
        }
    };

    const renderUserSchemaForm = ( user = { user_id : 0 } ) => {
        const sectionUi = document.createDocumentFragment ();
        const container = document.getElementById ( layoutId.details.innerSlideContainer );

        const submitSchemaForm = async e => {
            e.preventDefault ();
            const formInfo = {
                user_id : user.user_id,
                schema_content : codeEditor.getValue ()
            };
            const saveResponse = await bdApi.adminApiPost ( formInfo, `save-schema`, layoutOptions.mainWidget );

            if (saveResponse.info.save_status === 1) {
                dynamicTable.loadPage ();
                toast.renderToast ( {
                    icon : `fa fa-floppy-o`,
                    title : `Success`,
                    text : `Your new schema updates have been saved.`
                } );
            } else {
                dialog.centerDialog ( {
                    title : `Error`,
                    text : `There has been an error trying to perform this action, please reload the page and try it again.`,
                    showCancelButton : false,
                    confirmButtonText : `Ok`
                } );
            }
        };


        if ( user.user_id === 0 ) {
            sectionUi.append (
                dom.node ( `div`, { id : layoutId.details.memberPreview.container }, undefined, [
                    dom.node ( `span`, { id : layoutId.details.memberPreview.thumbnail } ),
                    dom.node ( `span`, { id : layoutId.details.memberPreview.name }, undefined, [ `Pick a member to update their schema data` ] )
                ] )
            );
        } else {
            const schemaFormSettings = {
                attributes : {
                    action : `#`,
                    type : `post`,
                    onsubmit : submitSchemaForm,
                    id : layoutId.details.form
                }
            };

            const schemaFormInputs = [
                {
                    attributes : {
                        value : () => {
                            const formatSchema = schema => {
                                if ( schema !== `` ) {
                                    const jsonValue = JSON.parse (  schema.replace ( `<script type="application/ld+json">`, `` ).replace( `</script>`, `` ) );
                                    const editorValue =  JSON.stringify( jsonValue, null, 4 );
                                    return `<script type="application/ld+json">
${editorValue} 
</script>`;
                                } else {
                                    return ``;
                                }
                            };

                            const editorDOMElement = dom.node ( `div`, { id : layoutId.details.formElements.editor.element } );
                            const editorUi = document.createDocumentFragment ();

                            const fullPageEditor = () => editorDOMElement.requestFullscreen ();

                            const loadDefaultSchema = () => codeEditor.setValue ( formatSchema ( user.default_schema ) );

                            editorUi.append (
                                dom.node ( `div`, { id : layoutId.details.formElements.editor.options.container }, undefined, [
                                    dom.node ( `button`, { type : `button`, id : layoutId.details.formElements.editor.options.defaultSchemaButton, onclick : loadDefaultSchema }, undefined, [ `Load Default Schema` ] ),
                                    dom.node ( `button`, { type : `button`, id : layoutId.details.formElements.editor.options.fullScreenButton, title : `Full Screen Editor`, onclick : fullPageEditor }, undefined, [
                                        dom.node ( `i`, { className : `fa fa-expand` } )
                                    ] )
                                ] ),
                                editorDOMElement
                            );
                            codeEditor = ace.edit ( editorDOMElement, {
                                mode: `ace/mode/php`,
                                selectionStyle: `text`
                            } );
                            codeEditor.setOption("wrap", true);
                            codeEditor.setTheme ( `ace/theme/monokai` );

                            if ( user.schema_content ) {
                                codeEditor.setValue ( formatSchema ( user.schema_content ) );
                            }
                            return editorUi;
                        }
                    },
                    settings : {
                        type : `customHtml`
                    }
                },
                {
                    attributes : {
                        type : `submit`,
                        value : dom.node ( `span`, undefined, undefined, [ `Save Schema Structure` ] ),
                        id : layoutId.details.formElements.submitButton
                    },
                    settings : {
                        type : `button`
                    }
                }
            ];

            const thumbnail = layoutElement.renderUserThumbImage ( user.user_info );
            thumbnail.id = layoutId.details.memberPreview.thumbnail;
            sectionUi.append (
                dom.node ( `div`, { id : layoutId.details.memberPreview.container }, undefined, [
                    thumbnail,
                    dom.node ( `span`, { id : layoutId.details.memberPreview.name }, undefined, [ user.user_info.full_name ] ),
                    dom.node ( `button`, { id : layoutId.details.memberPreview.closeButton, onclick : () => renderUserSchemaForm () }, undefined, [
                        dom.node ( `i`, { className : `fa fa-times` } )
                    ] )
                ] ),
                schemaForm.renderFormUi ( schemaFormSettings, schemaFormInputs )
            );
        }

        dom.emptyNodeContents ( container );

        container.append ( sectionUi );
    };

    const layoutUi = container => {
        const layoutContent = document.createDocumentFragment ();

        const dynamicTableSettings = {
            sqlEngineApiAction : `schema-dynamic-table`,
            sqlEngingeApi : layoutOptions.mainWidget,
            tableTitle : dom.simpleNode ( `h2`, undefined, undefined, [ `User Schema` ] ),
            loadPageCallback : () => {
                if ( document.getElementById ( layoutId.details.formElements.editor.element ) ) {
                    codeEditor.resize ();
                }
            },
            tableFiltersMap : [
                {
                    attributes : {
                        type : `text`,
                        name : `keyword`,
                        placeholder : `Keyword Search`
                    },
                    settings : {
                        type : `singleInput`,
                        display : `dynamicTableText`,
                        label : ``
                    }
                },
                {
                    attributes : {
                        name : `subscription_id`,
                        id : layoutId.table.dataTable.filters.subscriptionSelect
                    },
                    options : async () => {
                        const subscriptions = await bdApi.adminApiPost ( {}, `get-site-subscriptions`, layoutOptions.mainWidget );

                        return [
                            {
                                label : `Pick a Subscription`,
                                value : ``
                            },
                            ...subscriptions.info.subscriptions.map ( subscription => ( {
                                label : subscription.subscription_name,
                                value : subscription.subscription_id
                            } ) )
                        ];
                    },
                    settings : {
                        type : `selectDropdown`
                    }
                },
                {
                    attributes : {
                        value : () => {
                            const filterUi = document.createDocumentFragment ();

                            filterUi.append (
                                dom.node ( `label`, { id : layoutId.table.dataTable.filters.schemaFilter.container }, undefined, [
                                    dom.node ( `input`, { type : `checkbox`, id : layoutId.table.dataTable.filters.schemaFilter.input, name : `filter_schema`, value : `1` } ),
                                    dom.node ( `div`, { id : layoutId.table.dataTable.filters.schemaFilter.label }, undefined, [
                                        dom.node ( `i`, { className : `fa fa-square-o`, id : layoutId.table.dataTable.filters.schemaFilter.iconUnCheck } ),
                                        dom.node ( `i`, { className : `fa fa-check-square-o`, id : layoutId.table.dataTable.filters.schemaFilter.iconCheck } ),
                                        dom.node ( `span`, { id : layoutId.table.dataTable.filters.schemaFilter.text }, undefined, [ `Custom Schemas` ] )
                                    ] )
                                ] )
                            );

                            return filterUi;
                        }
                    },
                    settings : {
                        type : `customHtml`
                    }
                },
                {
                    attributes : {
                        id : layoutId.table.dataTable.filters.filterButton,
                        value : dom.node ( `span`, undefined, undefined, [ `Filter` ] )
                    },
                    settings : {
                        type : `button`
                    }
                }
            ],
            tableMapping : [
                {
                    label : `ID`,
                    infoFormatting : row => row.user_id,
                    sort : `user-id`
                },
                {
                    label : `User`,
                    infoFormatting : row => {
                        const cellUi = document.createDocumentFragment ();
                        const thumbnail = layoutElement.renderUserThumbImage ( row.user_info );
                        dom.nodeAddClass ( thumbnail, layoutClass.table.row.userInfo.thumbnail );
                        cellUi.append (
                            dom.node ( `div`, { className : layoutClass.table.row.userInfo.container }, undefined, [
                                thumbnail,
                                dom.node ( `span`, { className : layoutClass.table.row.userInfo.name }, undefined, [ row.user_info.full_name ] )
                            ] )
                        );
                        return cellUi;
                    },
                    sort : `user-name`
                },
                {
                    label : `Subscription`,
                    infoFormatting : row => row.subscription_name,
                    sort : `susbcription-name`
                },
                {
                    label : `Schema`,
                    infoFormatting : row => {
                        const cellUi = document.createDocumentFragment ();

                        if ( row.schema_content != null ) {
                            cellUi.append (
                                dom.node ( `div`, { className : layoutClass.table.row.schema.container }, undefined, [
                                    dom.node ( `div`, { className : layoutClass.table.row.schema.icon }, { status : `custom` }, [
                                        dom.node ( `i`, { className : `fa fa-pencil-square-o` } )
                                    ] ),
                                    dom.node ( `span`, { className : layoutClass.table.row.schema.status }, undefined, [ `CUSTOM SCHEMA ` ] ),
                                    dom.node ( `span`, { className : layoutClass.table.row.schema.createdAt }, undefined, [ `C: ${row.schema_created_at}` ] ),
                                    row.schema_updated_at !== `0000-00-00 00:00:00` && row.schema_updated_at !== `` ? dom.node ( `span`, { className : layoutClass.table.row.schema.updatedAt }, undefined, [ `U: ${row.schema_updated_at}` ] ) : ``
                                ] )
                            );
                        } else {
                            cellUi.append (
                                dom.node ( `div`, { className : layoutClass.table.row.schema.container }, undefined, [
                                    dom.node ( `div`, { className : layoutClass.table.row.schema.icon }, { status : `default` }, [
                                        dom.node ( `i`, { className : `fa fa-cubes` } )
                                    ] ),
                                    dom.node ( `span`, { className : layoutClass.table.row.schema.status }, undefined, [ `DEFAULT SCHEMA ` ] )
                                ] )
                            );
                        }

                        return cellUi;
                    }
                },
                {
                    label : `Actions`,
                    infoFormatting : row => dom.node ( `div`, { className : layoutClass.table.row.actions.container }, undefined, [
                        dom.node ( `button`, { className : layoutClass.table.row.actions.editButton, onclick : () => renderUserSchemaForm ( row ) }, undefined, [ `Edit Schema` ] )
                    ] )
                }
            ],
            resultsPerPage : 10,
            resutlsPerPageOptions : [ 10, 25, 50, 100 ],
            toolEnv : `admin`
        };

        dynamicTable = new DynamicTable ( dynamicTableSettings );

        layoutContent.append (
            dom.node ( `main`, { id : layoutId.container }, undefined, [
                dom.node ( `section`, { id : layoutId.table.container }, undefined, [
                    dynamicTable.renderComponentUi ()
                ] ),
                dom.node ( `section`, { id : layoutId.details.container }, undefined, [
                    dom.node ( `h3`, { id : layoutId.details.title }, undefined, [ `Details Manager` ] ),
                    dom.node ( `div`, { id : layoutId.details.innerSlideContainer }, undefined, [
                        dom.node ( `div`, { id : layoutId.details.memberPreview.container } )
                    ] )
                ] )
            ] )
        );

        container.append ( layoutContent );

        setTimeout ( () => {
            dynamicTable.loadPage ();
            renderUserSchemaForm ();
        },200);
    };

    this.renderUi = container => layoutUi ( container );
}

export { CatSchemaManager };