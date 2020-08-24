<form action="" class="wc-progress-form-content woocommerce-importer"
      method="post">
    <header>
        <h2></h2>
        <p></p>
    </header>
    <section>
        <table>
            <thead>
            <tr>
                <th></th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td class="wc-importer-mapping-table-name">
                    <span class="description"></span>
                </td>
                <td>
                    <input type="hidden">
                    <select name="" id="">
                        <optgroup>
                            <option value=""></option>
                        </optgroup>
                    </select>
                </td>
            </tr>
            </tbody>
        </table>
    </section>
    <div class="wc-actions">
        <button></button>
        <input type="hidden">
        <input type="hidden">
        <input type="hidden">
        <?= wp_nonce_field('extended-xls-importer') ?>
    </div>
</form>