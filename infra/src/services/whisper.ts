import * as pulumi from '@pulumi/pulumi';
import * as gcp from '@pulumi/gcp';
import * as docker from '@pulumi/docker';


export interface WhisperServiceConfig {
    project: string;
    region: string;
    laravelWebhookUrl: pulumi.Input<string>;
    webhookSecret: pulumi.Input<string>;
    minioEndpoint: pulumi.Input<string>;
    minioKey: pulumi.Input<string>;
    minioSecret: pulumi.Input<string>;
}


export class WhisperService extends pulumi.ComponentResource {
    public readonly serviceUrl: pulumi.Output<string>;

    constructor(name: string, config: WhisperServiceConfig, opts?: pulumi.ComponentResourceOptions) {
        super("transcriber:services:WhisperService", name, {}, opts);

        const repoId = "transcriber-services";
        const imageName = "whisper-service";

        const repository = new gcp.artifactregistry.Repository('transcriber-repo', {
            repositoryId: repoId,
            description: "Repositório para os serviços da aplicação de transcrição",
            format: "DOCKER",
            location: config.region,

        }, { parent: this });


        const fullyQualifiedImageName = pulumi.interpolate`${repository.location}-docker.pkg.dev/${config.project}/${repository.repositoryId}/${imageName}`;

        const whisperImage = new docker.Image('whisper-service-image', {
            imageName: fullyQualifiedImageName,
            build: {
                context: '../cloud-run-whisper',
                platform: "linux/amd64",
            },
        }, { parent: this });

        const whisperService = new gcp.cloudrun.Service("transcriber-whisper-service", {
            location: config.region,
            template: {
                spec: {
                    containers: [{
                        image: whisperImage.imageName,
                        resources: { limits: { memory: "2Gi", cpu: "1" } },
                        envs: [
                            { name: "LARAVEL_WEBHOOK_URL", value: config.laravelWebhookUrl },
                            { name: "WEBHOOK_SECRET", value: config.webhookSecret },
                            { name: "MINIO_ENDPOINT", value: config.minioEndpoint },
                            { name: "MINIO_KEY", value: config.minioKey },
                            { name: "MINIO_SECRET", value: config.minioSecret },
                        ],
                    }],
                },
            },
        }, { parent: this, dependsOn: [whisperImage] });


        new gcp.cloudrun.IamMember("allow-public-invocations", {
            location: whisperService.location,
            service: whisperService.name,
            role: "roles/run.invoker",
            member: "allUsers",
        }, { parent: this });

        this.serviceUrl = whisperService.statuses[0].url;

        this.registerOutputs({
            serviceUrl: this.serviceUrl,
        });
    }
}