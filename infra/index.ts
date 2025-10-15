import * as pulumi from "@pulumi/pulumi";
import { WhisperService } from "./src/services/whisper";
// import { LaravelService } from "./src/services/laravelService";



const config = new pulumi.Config();
const gcpConfig = new pulumi.Config("gcp");
const project = gcpConfig.require("project");
const region = gcpConfig.require("region");

//TODO: Implementar a URL do Webhook => pulumi config set laravel_webhook_url "https://COLE_AQUI_O_URL_DO_SEU_WEBHOOK"
//TODO: secret: pulumi config set --secret webhook_secret "SEU_SEGREDO_COMPARTILHADO_MUITO_FORTE"
const whisperInfra = new WhisperService("whisper-infra", {
    project: project,
    region: region,
    laravelWebhookUrl: config.require("laravel_webhook_url"),
    webhookSecret: config.requireSecret("webhook_secret"),
    minioEndpoint: config.require("minio_endpoint"),
    minioKey: config.requireSecret("minio_key"),
    minioSecret: config.requireSecret("minio_secret"),
});


// const laravelInfra = new LaravelService("laravel-infra", {
//     project: project,
//     region: region,
// });


export const whisperServiceUrl = whisperInfra.serviceUrl;
// export const laravelVmIp = laravelInfra.instanceIp;