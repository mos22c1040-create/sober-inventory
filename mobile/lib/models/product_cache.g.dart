// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'product_cache.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class ProductCacheAdapter extends TypeAdapter<ProductCache> {
  @override
  final int typeId = 0;

  @override
  ProductCache read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return ProductCache(
      id: fields[0] as int,
      name: fields[1] as String,
      sku: fields[2] as String?,
      price: fields[3] as double,
      cost: fields[4] as double,
      quantity: fields[5] as int,
      lowStockThreshold: fields[6] as int,
      unit: fields[7] as String,
      categoryId: fields[8] as int?,
      typeId: fields[9] as int?,
      categoryName: fields[10] as String?,
      typeName: fields[11] as String?,
      lastUpdated: fields[12] as DateTime,
    );
  }

  @override
  void write(BinaryWriter writer, ProductCache obj) {
    writer
      ..writeByte(13)
      ..writeByte(0)
      ..write(obj.id)
      ..writeByte(1)
      ..write(obj.name)
      ..writeByte(2)
      ..write(obj.sku)
      ..writeByte(3)
      ..write(obj.price)
      ..writeByte(4)
      ..write(obj.cost)
      ..writeByte(5)
      ..write(obj.quantity)
      ..writeByte(6)
      ..write(obj.lowStockThreshold)
      ..writeByte(7)
      ..write(obj.unit)
      ..writeByte(8)
      ..write(obj.categoryId)
      ..writeByte(9)
      ..write(obj.typeId)
      ..writeByte(10)
      ..write(obj.categoryName)
      ..writeByte(11)
      ..write(obj.typeName)
      ..writeByte(12)
      ..write(obj.lastUpdated);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProductCacheAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
