# TARS如何集成到PHP框架

该文档列举了PHP框架集成Tars的一般思路和需要考虑的问题。希望能在Tars和PHP框架集成方面给开发者们一些启发。

1. 复用phptars组件，包括tars-server、tars-log、tars-monitor、tars-registry等
2. Swoole和框架的请求及响应的上下文转换

![Tars-Laravel HTTP请求过程](./tars-laravel-http-request.png)

3. 拉取Tars Config，合并为框架的配置项
4. 集成Tars Log到框架的日志组件中

tars-log自带了monolog handler，可以比较方便的集成到使用monolog作为日志引擎的框架，比如Laravel。
在没有使用monolog作为日志引擎的框架中，可以编写相应的handler来扩展日志输出的方式，比如Yii2 Log Target。

5. 主动释放框架和PHP的某些全局资源，防止内存泄漏
