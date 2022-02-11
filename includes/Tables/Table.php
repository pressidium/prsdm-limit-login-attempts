<?php
namespace Pressidium\Limit_Login_Attempts\Tables;

use Pressidium\Limit_Login_Attempts\Interfaces\UI;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class Table implements UI {

    /**
     * @var array Column slugs.
     */
    private $col_slugs;

    /**
     * Table constructor.
     */
    public function __construct() {
        $this->col_slugs = array_keys( $this->get_cols() );
    }

    /**
     * Return the rows.
     *
     * @return array An indexed array containing an array for each row.
     */
    abstract protected function get_rows();

    /**
     * Return the columns.
     * 
     * @return array An associative array where its keys are column
     *               slugs and its values are column labels.
     */
    abstract protected function get_cols();

    /**
     * Return the CSS classes.
     *
     * @return array An indexed array containing CSS classes.
     */
    abstract protected function get_css_classes();

    /**
     * Render the table header.
     */
    private function render_header() {
        ?>

        <thead>
            <tr>
        
            <?php
            foreach ( $this->get_cols() as $col_slug => $col_label ) {
                ?>

                <th
                    scope="col"
                    id="<?php echo esc_attr( $col_slug ); ?>"
                    class="column column-<?php echo esc_attr( $col_slug ); ?>"
                >
                    <?php echo esc_html( $col_label ); ?>
                </th>

                <?php
            }
            ?>

            </tr>
        </thead>

        <?php
    }

    /**
     * Render the given row.
     *
     * @param array $row
     */
    private function render_row( $row ) {
        foreach ( $this->col_slugs as $col_slug ) {
            ?>

            <td class="column-<?php echo esc_attr( $col_slug ); ?>">
                <?php echo esc_html( $row[ $col_slug ] ); ?>
            </td>

            <?php
        }
    }

    /**
     * Render the table body.
     */
    private function render_body() {
        ?>

        <tbody>
        
            <?php foreach ( $this->get_rows() as $row ): ?>

                <tr>
                    <?php $this->render_row( $row ); ?>
                </tr>

            <?php endforeach; ?>

        </tbody>

        <?php
    }

    /**
     * Render the table.
     */
    public function render() {
        /** @noinspection SpellCheckingInspection */
        $css_classes = array_unique( array_merge(
            array( 'widefat', 'striped' ),
            $this->get_css_classes()
        ) );
        ?>

        <table class="<?php echo esc_attr( implode( ' ', $css_classes ) ); ?>">
            <?php
            $this->render_header();
            $this->render_body();
            ?>
        </table>

        <?php
    }

}
