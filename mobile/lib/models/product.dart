import '../utils/api_parse.dart';

class Product {
  final int id;
  final String name;
  final String? sku;
  final double price;
  final int quantity;
  final int? typeId;

  Product({
    required this.id,
    required this.name,
    this.sku,
    required this.price,
    required this.quantity,
    this.typeId,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: toInt(json['id']),
      name: (json['name'] ?? '').toString(),
      sku: json['sku'] != null ? json['sku'].toString() : null,
      price: toDouble(json['price']),
      quantity: toInt(json['quantity']),
      typeId: json['type_id'] != null ? toInt(json['type_id']) : null,
    );
  }
}

