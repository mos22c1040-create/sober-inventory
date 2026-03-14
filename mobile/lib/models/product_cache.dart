import 'package:hive/hive.dart';

part 'product_cache.g.dart';

@HiveType(typeId: 0)
class ProductCache extends HiveObject {
  @HiveField(0)
  int id;

  @HiveField(1)
  String name;

  @HiveField(2)
  String? sku;

  @HiveField(3)
  double price;

  @HiveField(4)
  double cost;

  @HiveField(5)
  int quantity;

  @HiveField(6)
  int lowStockThreshold;

  @HiveField(7)
  String unit;

  @HiveField(8)
  int? categoryId;

  @HiveField(9)
  int? typeId;

  @HiveField(10)
  String? categoryName;

  @HiveField(11)
  String? typeName;

  @HiveField(12)
  DateTime lastUpdated;

  ProductCache({
    required this.id,
    required this.name,
    this.sku,
    required this.price,
    required this.cost,
    required this.quantity,
    required this.lowStockThreshold,
    required this.unit,
    this.categoryId,
    this.typeId,
    this.categoryName,
    this.typeName,
    required this.lastUpdated,
  });

  factory ProductCache.fromJson(Map<String, dynamic> json) {
    return ProductCache(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      sku: json['sku'] as String?,
      price: (json['price'] as num?)?.toDouble() ?? 0.0,
      cost: (json['cost'] as num?)?.toDouble() ?? 0.0,
      quantity: json['quantity'] as int? ?? 0,
      lowStockThreshold: json['low_stock_threshold'] as int? ?? 5,
      unit: json['unit'] as String? ?? 'قطعة',
      categoryId: json['category_id'] as int?,
      typeId: json['type_id'] as int?,
      categoryName: json['category_name'] as String?,
      typeName: json['type_name'] as String?,
      lastUpdated: DateTime.now(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'sku': sku,
      'price': price,
      'cost': cost,
      'quantity': quantity,
      'low_stock_threshold': lowStockThreshold,
      'unit': unit,
      'category_id': categoryId,
      'type_id': typeId,
      'category_name': categoryName,
      'type_name': typeName,
    };
  }
}