
# coco-codeParser

一个面向 PHP 项目的结构化代码抽取器。

它的作用是把 PHP 源码项目抽取成统一的 JSONL 数据，供后续的 Python Code RAG 系统使用。

当前输出包含：

- `file`
- `symbol`
- `relation`

并且已经支持：

- class / interface / trait / enum
- method / function
- property
- class_const
- enum_case
- docblock 结构化
- attributes
- parameter native/docblock 双轨类型
- file strict_types / declared_symbols

---

# 1. 项目定位

`coco-codeParser` 不是一个完整静态分析器，也不是一个代码执行器。

它的目标是：

1. 从 PHP 项目中抽取**稳定、结构化、适合索引**的数据
2. 为后续的搜索、summary、问答系统提供统一输入
3. 尽量保留：
- 代码结构
- 类型信息
- 注释信息
- 关系信息
- embedding 友好的文本

它尤其适合作为：

- PHP Code RAG 的前置抽取层
- 代码智能问答系统的基础数据层
- 结构化代码索引系统的数据准备工具

---

# 2. 当前能力

## 2.1 文件层
支持输出 `file` 记录，包含：

- `id`
- `language`
- `file_path`
- `relative_path`
- `namespace`
- `imports`
- `symbols`
- `strict_types`
- `declared_symbols`

---

## 2.2 符号层
支持输出 `symbol` 记录，包含以下 kind：

- `class`
- `interface`
- `trait`
- `enum`
- `method`
- `function`
- `property`
- `class_const`
- `enum_case`

---

## 2.3 文档层
支持：

- `doc_comment`
- `docblock`

其中 `docblock` 结构化字段包括：

- `summary`
- `description`
- `params`
- `return_type`
- `throws`
- `is_deprecated`
- `tags`

---

## 2.4 类型层

### 参数类型
当前 method / function 参数支持：

- `native_type`
- `docblock_type`
- `description`

### 其他类型字段
还支持：

- `return_type`
- `declared_type`
- `value`

---

## 2.5 Attributes
支持提取：

- class attributes
- function attributes
- method attributes
- property attributes
- class_const attributes
- enum_case attributes

---

## 2.6 关系层
支持输出 `relation` 记录，当前支持：

- `defined_in`
- `belongs_to`
- `extends`
- `implements`
- `uses_trait`

---

## 2.7 向量文本层
每个 symbol 还会生成：

- `embedding_text`

其中包含适合向量检索的文本信息，例如：

- kind
- fqname
- file
- signature
- docblock
- attributes
- parameters
- code

---

# 3. 安装

## 3.1 PHP 版本
请使用满足 `composer.json` 要求的 PHP 版本。

推荐至少使用较新的 PHP 8.x 环境。

---

## 3.2 安装依赖

在项目根目录执行：

```bash
composer install
```

如果要更新依赖：

```bash
composer update
```

---

# 4. 依赖说明

当前 extractor 主要依赖：

- `nikic/php-parser`
- 相关 docblock / AST 处理组件

它们用于：

- AST 解析
- Name Resolver
- DocBlock 结构化提取

---

# 5. 基本使用方式

## 5.1 标准命令

```bash
php bin/extract.php --project-root=/path/to/php/project --output=result.jsonl
```

---

## 5.2 示例

```bash
php bin/extract.php --project-root=/var/www/6025/new/coco-base64 --output=coco_base64.result.jsonl
```

---

# 6. 参数说明

## `--project-root`
指定要抽取的 PHP 项目根目录。

示例：

```bash
--project-root=/var/www/6025/new/coco-base64
```

这个目录通常应当是：

- Composer 项目根目录
- 或至少是一个完整 PHP 源码项目目录

---

## `--output`
指定输出 JSONL 文件路径。

示例：

```bash
--output=coco_base64.result.jsonl
```

推荐输出到：

- Python 搜索系统约定的 output 路径
- 或专门的数据输出目录

---

# 7. 输出格式说明

输出是 JSONL（JSON Lines）格式。

即：

- 每一行一条 JSON 记录
- 不同类型记录会混合输出在同一个文件中

当前记录类型包括：

- `file`
- `symbol`
- `relation`

---

# 8. 记录示例说明

## 8.1 file record
典型字段：

- `record_type`
- `id`
- `language`
- `file_path`
- `relative_path`
- `namespace`
- `imports`
- `symbols`
- `strict_types`
- `declared_symbols`

---

## 8.2 symbol record
典型字段：

- `record_type`
- `id`
- `kind`
- `name`
- `display_name`
- `fqname`
- `namespace`
- `class_name`
- `file_path`
- `relative_path`
- `start_line`
- `end_line`
- `visibility`
- `is_static`
- `is_abstract`
- `is_final`
- `parameters`
- `return_type`
- `value`
- `declared_type`
- `doc_comment`
- `docblock`
- `attributes`
- `signature`
- `code`
- `extends`
- `implements`
- `traits`
- `embedding_text`

---

## 8.3 relation record
典型字段：

- `record_type`
- `id`
- `relation_type`
- `from_id`
- `from_symbol`
- `to_id`
- `to_symbol`
- `file_path`
- `metadata`

---

# 9. 当前支持的 symbol 细节

## 9.1 method / function
支持提取：

- signature
- parameters
- native/docblock 类型
- return_type
- doc_comment
- docblock
- attributes
- code

---

## 9.2 property
支持提取：

- visibility
- is_static
- declared_type
- value
- doc_comment
- docblock
- attributes
- signature
- code

---

## 9.3 class_const
支持提取：

- visibility
- value
- declared_type
- doc_comment
- docblock
- attributes
- signature
- code

---

## 9.4 enum_case
支持提取：

- value
- doc_comment
- docblock
- attributes
- signature
- code

---

# 10. 当前设计说明

## 10.1 为什么要抽 embedding_text
因为后续 Python 搜索层需要把 symbol 信息送入向量库。

单纯代码片段通常不够稳定，因此会把：

- 签名
- 注释
- 参数
- 属性
- 代码

拼成统一的 `embedding_text`。

---

## 10.2 为什么要保留 docblock 结构化信息
因为很多 PHP 项目的语义信息不完全在原生类型里，而是在 docblock 里。

例如：

- 参数说明
- 返回类型
- throws
- deprecated

所以系统同时保留：

- 原始 `doc_comment`
- 结构化 `docblock`

---

## 10.3 为什么参数要分 native_type / docblock_type
因为 PHP 项目中常见情况是：

- 原生签名类型不完整
- docblock 类型更详细
- 两者不完全等价

所以系统采用双轨保留策略。

---

# 11. 当前边界说明

当前 extractor 已经适合做 Code RAG 前置数据层，但仍有一些边界：

## 已经支持
- 结构提取
- docblock 结构化
- attributes
- property / class_const / enum_case
- file strict_types / declared_symbols
- 适合 embedding 的 symbol 文本

## 当前还不支持或不完整支持
- extractor 自身增量抽取
- 完整调用图
- 完整异常传播链
- PHPStan / Psalm 深度推断集成
- trait method provenance
- 重度静态语义分析

---

# 12. 推荐搭配方式

推荐把本项目作为：

- PHP 结构化抽取层

然后搭配 Python 搜索/问答系统：

- 向量索引
- 增量索引
- summary
- hybrid search
- rerank
- QA

---

# 13. 推荐使用流程

假设你已经有一个 PHP 项目：

```bash
/var/www/6025/new/coco-base64
```

那么推荐：

## 13.1 先测试 extractor

```bash
php bin/extract.php --project-root=/var/www/6025/new/coco-base64 --output=/tmp/coco_base64.result.jsonl
```

## 13.2 检查输出

```bash
head -n 20 /tmp/coco_base64.result.jsonl
```

## 13.3 再交给 Python 系统做索引和问答

Python 侧正式处理注册项目时，推荐一定用：

```bash
python -m app.cli --project-name coco_base64 pipeline --mode api --incremental
```

---

# 14. 常见命令

## 安装依赖

```bash
composer install
```

## 查看依赖

```bash
composer show
```

## 刷新 autoload

```bash
composer dump-autoload
```

## 运行抽取

```bash
php bin/extract.php --project-root=/path/to/project --output=result.jsonl
```

---

# 15. 当前版本结论

当前 `coco-codeParser` 已经可以作为一个较完整、较稳定的 PHP 结构化抽取层使用。

它适合作为：

- 内部代码问答系统前置数据构建器
- PHP 项目结构化索引生成器
- Code RAG 的基础抽取组件

如果后续继续增强，优先方向建议是：

1. extractor 自身增量化
2. 更强 relation / call graph
3. 更深静态分析
4. PHPStan / Psalm 集成

