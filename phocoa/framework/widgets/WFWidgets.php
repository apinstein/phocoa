<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * @package UI
 * @copyright Copyright (c) 2005 Alan Pinstein. All Rights Reserved.
 * @version $Id: kvcoding.php,v 1.3 2004/12/12 02:44:09 alanpinstein Exp $
 * @author Alan Pinstein <apinstein@mac.com>                        
 */

/**
 * Automated setup for widget system.
 *
 *
 *
 */
WFValueTransformer::setValueTransformerForName(new WFNegateBooleanTransformer, 'WFNegateBoolean');
WFValueTransformer::setValueTransformerForName(new WFIsEmptyTransformer, 'WFIsEmpty');
WFValueTransformer::setValueTransformerForName(new WFIsNotEmptyTransformer, 'WFIsNotEmpty');

?>
