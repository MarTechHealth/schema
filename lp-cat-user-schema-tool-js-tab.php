<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.6/ace.js" ></script>

<script type="module">
    import { CatSchemaManager } from 'https://martech-health-sp-1.nyc3.digitaloceanspaces.com/dist/layouts/js/catSchemaManager.min.js';
    const catSchemaManager = new CatSchemaManager ();

    catSchemaManager.renderUi ( document.getElementById ( `lp-cat-user-schema-manager-root` ) );
</script>
