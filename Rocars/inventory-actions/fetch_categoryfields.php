<?php
require_once '../includes/config.php';
header('content-type:application/json');

$category_id = $_GET['category_id'] ?? 0;
$fields= [];

switch($category_id){
    case 1: // accessories
        $fields = [
            ['name'=>'product_name','label'=>'Product Name','type'=>'text','required'=>true],
            ['name'=>'typeofaccessories','label'=>'Type of Accessories','type'=>'text'],
            ['name'=>'brand','label'=>'Brand','type'=>'text'],
            ['name'=>'model_number','label'=>'Model Number','type'=>'text'],
            ['name'=>'material','label'=>'Material','type'=>'text'],
            ['name'=>'color','label'=>'Color','type'=>'text'],
            ['name'=>'fitment_details','label'=>'Fitment Details','type'=>'text'],
            ['name'=>'quantity','label'=>'Quantity','type'=>'text','step'=>'any'],
            ['name'=>'price','label'=>'Price','type'=>'text','required'=>true,'step'=>'any'],
            ['name'=>'cost','label'=>'Cost','type'=>'text','step'=>'any'],
            ['name'=>'critical','label'=>'Critical stock level','type'=>'text','step'=>'any']
        ];  
    break;

    case 2: // battery
        $fields = [
            ['name'=>'product_name','label'=>'Product Name','type'=>'text','required'=>true],
            ['name'=>'brand','label'=>'Brand','type'=>'text'],
            ['name'=>'Voltage','label'=>'Voltage','type'=>'text'],
            ['name'=>'Model_number','label'=>'Model Number','type'=>'text'],
            ['name'=>'quantity','label'=>'Quantity','type'=>'text','step'=>'any'],
            ['name'=>'price','label'=>'Price','type'=>'text','required'=>true,'step'=>'any'],
            ['name'=>'cost','label'=>'Cost','type'=>'text','step'=>'any'],
            ['name'=>'critical','label'=>'Critical stock level','type'=>'text','step'=>'any']
        ];  
    break;

    case 3: // engine oil
        $fields = [
            ['name'=>'product_name','label'=>'Product Name','type'=>'text','required'=>true],
            ['name'=>'brand','label'=>'Brand','type'=>'text'],
            ['name'=>'oiltype','label'=>'Oil Type','type'=>'text'],
            ['name'=>'capacity','label'=>'Capacity','type'=>'text'],
            ['name'=>'quantity','label'=>'Quantity','type'=>'text','step'=>'any'],
            ['name'=>'price','label'=>'Price','type'=>'text','required'=>true,'step'=>'any'],
            ['name'=>'cost','label'=>'Cost','type'=>'text','step'=>'any'],
            ['name'=>'critical','label'=>'Critical stock level','type'=>'text','step'=>'any']
        ];  
    break;

    case 4: // filter
        $fields = [
            ['name'=>'product_name','label'=>'Product Name','type'=>'text','required'=>true],
            ['name'=>'brand','label'=>'Brand','type'=>'text'],
            ['name'=>'typeoffilter','label'=>'Type of Filter','type'=>'text'],
            ['name'=>'vehicle_application','label'=>'Vehicle Application','type'=>'text'],
            ['name'=>'filter_specs','label'=>'Filter Spec','type'=>'text'],
            ['name'=>'material','label'=>'Housing Type','type'=>'text'],
            ['name'=>'quantity','label'=>'Quantity','type'=>'text','step'=>'any'],
            ['name'=>'price','label'=>'Price','type'=>'text','required'=>true,'step'=>'any'],
            ['name'=>'cost','label'=>'Cost','type'=>'text','step'=>'any'],
            ['name'=>'critical','label'=>'Critical stock level','type'=>'text','step'=>'any']
        ];  
    break;

    case 5: // lugnuts
        $fields = [
            ['name'=>'product_name','label'=>'Product Name','type'=>'text','required'=>true],
            ['name'=>'typeoflugnuts','label'=>'Type of Lugnuts','type'=>'text'],
            ['name'=>'size','label'=>'Size','type'=>'text','step'=>'any'],
            ['name'=>'quantity','label'=>'Quantity','type'=>'text','step'=>'any'],
            ['name'=>'price','label'=>'Price','type'=>'text','required'=>true,'step'=>'any'],
            ['name'=>'cost','label'=>'Cost','type'=>'text','step'=>'any'],
            ['name'=>'critical','label'=>'Critical stock level','type'=>'text','step'=>'any']
        ];  
    break;

    case 6: // mags
        $fields = [
            ['name'=>'product_name','label'=>'Product Name','type'=>'text','required'=>true],
            ['name'=>'brand','label'=>'Brand','type'=>'text'],
            ['name'=>'model','label'=>'Model','type'=>'text'],
            ['name'=>'size','label'=>'Size','type'=>'text'], // text is fine for mags
            ['name'=>'material','label'=>'Material','type'=>'text'],
            ['name'=>'quantity','label'=>'Quantity','type'=>'text','step'=>'any'],
            ['name'=>'price','label'=>'Price','type'=>'text','required'=>true,'step'=>'any'],
            ['name'=>'cost','label'=>'Cost','type'=>'text','step'=>'any'],
            ['name'=>'critical','label'=>'Critical stock level','type'=>'text','step'=>'any']
        ];
    break;

    case 7: // mechanical product
        $fields = [
            ['name'=>'product_name','label'=>'Product Name','type'=>'text','required'=>true],
            ['name'=>'part_name','label'=>'Part Name','type'=>'text'],
            ['name'=>'made','label'=>'Made','type'=>'text'],
            ['name'=>'model','label'=>'Model','type'=>'text'],
            ['name'=>'technical_spec','label'=>'Technical Spec','type'=>'text'],
            ['name'=>'quantity','label'=>'Quantity','type'=>'text','step'=>'any'],
            ['name'=>'price','label'=>'Price','type'=>'text','required'=>true,'step'=>'any'],
            ['name'=>'cost','label'=>'Cost','type'=>'text','step'=>'any'],
            ['name'=>'critical','label'=>'Critical stock level','type'=>'text','step'=>'any']
        ];  
    break;

    case 9: // tire
        $fields = [
            ['name'=>'product_name','label'=>'Product Name','type'=>'text','required'=>true],
            ['name'=>'brand','label'=>'Brand','type'=>'text'],
            ['name'=>'size','label'=>'Size','type'=>'text'],
            ['name'=>'pattern','label'=>'Pattern','type'=>'text'],
            ['name'=>'made','label'=>'Made','type'=>'text'],
            ['name'=>'quantity','label'=>'Quantity','type'=>'text','step'=>'any'],
            ['name'=>'price','label'=>'Price','type'=>'text','required'=>true,'step'=>'any'],
            ['name'=>'cost','label'=>'Cost','type'=>'text','step'=>'any'],
            ['name'=>'critical','label'=>'Critical stock level','type'=>'text','step'=>'any']
        ];  
    break;

    case 10: // tire valve
        $fields = [
            ['name'=>'product_name','label'=>'Product Name','type'=>'text','required'=>true],
            ['name'=>'valvetype','label'=>'Valve Type','type'=>'text'],
            ['name'=>'material','label'=>'Material','type'=>'text'],
            ['name'=>'color','label'=> 'Color','type'=>'text'],
            ['name'=>'quantity','label'=>'Quantity','type'=>'text','step'=>'any'],
            ['name'=>'price','label'=>'Price','type'=>'text','required'=>true,'step'=>'any'],
            ['name'=>'cost','label'=>'Cost','type'=>'text','step'=>'any'],
            ['name'=>'critical','label'=>'Critical stock level','type'=>'text','step'=>'any']
        ];  
    break;

    case 11: // wheelweights
        $fields = [
            ['name'=>'product_name','label'=>'Product Name','type'=>'text','required'=>true],
            ['name'=>'model','label'=>'Model','type'=>'text'],
            ['name'=>'weight','label'=>'Weight','type'=>'text'],
            ['name'=>'material','label'=>'Material','type'=>'text'],
            ['name'=>'quantity','label'=>'Quantity','type'=>'text','step'=>'any'],
            ['name'=>'price','label'=>'Price','type'=>'text','required'=>true,'step'=>'any'],
            ['name'=>'cost','label'=>'Cost','type'=>'text','step'=>'any'],
            ['name'=>'critical','label'=>'Critical stock level','type'=>'text','step'=>'any']
        ];  
    break;

    case 12: // motorcycle tires
        $fields = [
            ['name'=>'product_name','label'=>'Product Name','type'=>'text','required'=>true],
            ['name'=>'brand','label'=>'Brand','type'=>'text'],
            ['name'=>'model','label'=>'Model','type'=>'text'],
            ['name'=>'type','label'=>'Type','type'=>'text'],
            ['name'=>'size','label'=>'Size','type'=>'text'],
            ['name'=>'quantity','label'=>'Quantity','type'=>'text','step'=>'any'],
            ['name'=>'price','label'=>'Price','type'=>'text','required'=>true,'step'=>'any'],
            ['name'=>'cost','label'=>'Cost','type'=>'text','step'=>'any'],
            ['name'=>'critical','label'=>'Critical stock level','type'=>'text','step'=>'any']
        ];  
    break;

    case 13: // others
        $fields = [
            ['name'=>'product_name','label'=>'Product Name','type'=>'text','required'=>true],
            ['name'=>'description','label'=>'Description','type'=>'text'],
            ['name'=>'quantity','label'=>'Quantity','type'=>'text','step'=>'any'],
            ['name'=>'price','label'=>'Price','type'=>'text','required'=>true,'step'=>'any'],
            ['name'=>'cost','label'=>'Cost','type'=>'text','step'=>'any'],
            ['name'=>'critical','label'=>'Critical stock level','type'=>'text','step'=>'any']
        ];  
    break;

    default:
        $fields = [
            ['name'=>'product_name','label'=>'Product Name','type'=>'text','required'=>true],
            ['name'=>'detail1','label'=>'Detail 1','type'=>'text'],
            ['name'=>'detail2','label'=>'Detail 2','type'=>'text'],
            ['name'=>'detail3','label'=>'Detail 3','type'=>'text'],
            ['name'=>'detail4','label'=>'Detail 4','type'=>'text'],
            ['name'=>'detail5','label'=>'Detail 5','type'=>'text'],
            ['name'=>'detail6','label'=>'Detail 6','type'=>'text'],
            ['name'=>'quantity','label'=>'Quantity','type'=>'text','step'=>'any'],
            ['name'=>'price','label'=>'Price','type'=>'text','required'=>true,'step'=>'any'],
            ['name'=>'cost','label'=>'Cost','type'=>'text','step'=>'any'],
            ['name'=>'critical','label'=>'Critical stock level','type'=>'text','step'=>'any']
        ];
}

echo json_encode($fields);
exit;
?>
