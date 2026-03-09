<?php
$file = __DIR__ . '/../views/products/index.php';
$c = file_get_contents($file);

// Replace the entire deleteProduct function region using raw bytes
// confirm('Delete product "' + name + '"?')) - uses curly/smart quotes in the file
$old = "confirm(\x27Delete product \xe2\x80\x9c\xe2\x80\x98 + name + \x27\xe2\x80\x9d?\x27))";
$new = "confirm('هل أنت متأكد من حذف المنتج \"' + name + '\"?\\nلا يمكن التراجع عن هذه العملية.'))";

if (strpos($c, $old) !== false) {
    $c = str_replace($old, $new, $c);
    file_put_contents($file, $c);
    echo "Fixed (attempt 1).\n";
    exit;
}

// Second attempt: maybe spaces differ
$search = "\x63\x6f\x6e\x66\x69\x72\x6d\x28\x27\x44\x65\x6c\x65\x74\x65\x20\x70\x72\x6f\x64\x75\x63\x74\x20\xe2\x80\x9c\xe2\x80\x98\x20\x2b\x20\x6e\x61\x6d\x65\x20\x2b\x20\x27\xe2\x80\x9d\x3f\x27\x29\x29\x20\x72\x65\x74\x75\x72\x6e\x3b";
$replace = "confirm('هل أنت متأكد من حذف المنتج \"' + name + '\"?\\nلا يمكن التراجع عن هذه العملية.')) return;";

if (strpos($c, $search) !== false) {
    $c = str_replace($search, $replace, $c);
    file_put_contents($file, $c);
    echo "Fixed (attempt 2).\n";
    exit;
}

echo "Not found. Trying to find and display surrounding context...\n";
$pos = strpos($c, "Delete product");
if ($pos !== false) {
    $start = max(0, $pos - 15);
    echo bin2hex(substr($c, $start, 80)) . "\n";
}
