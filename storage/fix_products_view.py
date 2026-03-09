import re

file = r'c:\Users\mustafa\Desktop\sober\views\products\index.php'
with open(file, 'r', encoding='utf-8') as f:
    c = f.read()

c = c.replace(
    "if (!confirm('Delete product \"' + name + '\"?')) return;",
    "if (!confirm('هل أنت متأكد من حذف المنتج \"' + name + '\"?\\nلا يمكن التراجع عن هذه العملية.')) return;"
)
c = c.replace(
    "else alert(data.error || 'Failed to delete');",
    "else alert(data.error || 'حدث خطأ أثناء الحذف');"
)
c = c.replace(
    "    });\n}",
    "    }).catch(function() { alert('خطأ في الاتصال بالخادم'); });\n}",
    1
)

with open(file, 'w', encoding='utf-8') as f:
    f.write(c)
print('done')
