<?php
/**
 * Decide si un producto concreto lleva aviso.
 *
 * El aviso es obligatorio en la venta B2C de **bienes**. Lo que queda fuera son los contratos que
 * no son compraventa de bienes —servicios y contenido digital puro, que van por la Directiva
 * (UE) 2019/770 y no por la 2019/771—, y de ahí que tenga sentido excluir catálogo. La regla la
 * fija el comerciante: el módulo da la herramienta y dice cuál es el criterio, no decide por él.
 *
 * @author BK Modules
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class BkGuaranteeScope
{
    /**
     * Se evalúa de lo más específico a lo más general: una exclusión concreta gana siempre a una
     * inclusión por categoría, que es lo que espera quien configura esto.
     *
     * @param int $idProduct
     *
     * @return bool
     */
    public static function appliesTo($idProduct)
    {
        $idProduct = (int) $idProduct;
        if ($idProduct <= 0) {
            // Sin producto identificable no se puede aplicar ninguna regla de catálogo, y dejar de
            // pintar el aviso por eso sería peor: se muestra.
            return true;
        }

        if (in_array($idProduct, BkGuaranteeConfig::getExcludedProducts(), true)) {
            return false;
        }

        if (BkGuaranteeConfig::isOn(BkGuaranteeConfig::SKIP_VIRTUAL) && self::isVirtual($idProduct)) {
            return false;
        }

        $categories = self::categoriesOf($idProduct);

        $excluded = BkGuaranteeConfig::getExcludedCategories();
        if (!empty($excluded) && array_intersect($excluded, $categories)) {
            return false;
        }

        if (BkGuaranteeConfig::getScopeMode() === 'categories') {
            $included = BkGuaranteeConfig::getIncludedCategories();

            return !empty($included) && (bool) array_intersect($included, $categories);
        }

        return true;
    }

    /**
     * @param int $idProduct
     *
     * @return array Identificadores de categoría del producto
     */
    private static function categoriesOf($idProduct)
    {
        $rows = Product::getProductCategories($idProduct);

        return array_map('intval', (array) $rows);
    }

    /**
     * @param int $idProduct
     *
     * @return bool
     */
    private static function isVirtual($idProduct)
    {
        return (bool) Db::getInstance()->getValue(
            'SELECT `is_virtual` FROM `' . _DB_PREFIX_ . 'product` WHERE `id_product` = ' . (int) $idProduct
        );
    }
}
